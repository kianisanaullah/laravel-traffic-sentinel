<?php

namespace Kianisanaullah\TrafficSentinel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class IpDataInstallCommand extends Command
{
    protected $signature = 'traffic-sentinel:ipdata:install
        {--force : Re-download and overwrite existing ipdata}
        {--url= : Override download URL}
        {--path= : Override install path (relative to disk root, e.g. traffic-sentinel/ipdata)}
        {--keep-zip : Keep the downloaded zip after install}';

    protected $description = 'Download and install Traffic Sentinel IP data (country zones + ASN datasets).';

    public function handle(): int
    {
        // 1) enabled switch
        if (!(bool) config('traffic-sentinel.ip_lookup.enabled', true)) {
            $this->error("traffic-sentinel.ip_lookup.enabled is false. Enable it in config/traffic-sentinel.php");
            return self::FAILURE;
        }

        // 2) resolve disk + install path (match your RuntimeIpLookupService)
        $disk = (string) config('traffic-sentinel.ip_lookup.storage.disk', 'local');

        // This should match RuntimeIpLookupService default (traffic-sentinel/ipdata)
        $installRel = (string) ($this->option('path')
            ?: config('traffic-sentinel.ip_lookup.storage.path')
                ?: 'traffic-sentinel/ipdata');

        $installRel = trim($installRel, "/ \t\n\r\0\x0B");

        // 3) resolve download URL (priority: CLI option -> config -> env)
        $url = (string) ($this->option('url')
            ?: config('traffic-sentinel.ip_lookup.download.url')
                ?: config('traffic-sentinel.ipdata.download_url')
                    ?: config('traffic-sentinel.ipdata.download.url')
        );

        if (!$url) {
            $this->error("No download URL configured.
Set traffic-sentinel.ip_lookup.download.url in config/traffic-sentinel.php
or run with: php artisan traffic-sentinel:ipdata:install --url=YOUR_URL");
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        // 4) If already installed and not forcing
        if (!$force && $this->alreadyInstalled($disk, $installRel)) {
            $this->info('IP data already installed. Use --force to reinstall.');
            $this->line('Disk: ' . $disk);
            $this->line('Path: ' . $installRel);
            return self::SUCCESS;
        }

        // 5) Prepare dirs
        Storage::disk($disk)->makeDirectory($installRel);

        $zipRel = rtrim($installRel, '/') . '/ipdata.zip';
        $zipAbs = Storage::disk($disk)->path($zipRel);
        $extractToAbs = Storage::disk($disk)->path($installRel);

        // 6) Force: wipe BEFORE download (clean install)
        if ($force) {
            $this->warn('Force enabled: wiping existing ipdata directory...');
            $this->wipeInstallDir($disk, $installRel, basename($zipRel));
        }

        // 7) Download zip
        $this->info('Downloading ipdata.zip...');
        $this->line('From: ' . $url);

        $response = Http::timeout((int) config('traffic-sentinel.ip_lookup.download.timeout', 120))
            ->withOptions(['stream' => true])
            ->get($url);

        if (!$response->ok()) {
            $this->error('Download failed with status: ' . $response->status());
            return self::FAILURE;
        }

        $bodyStream = $response->toPsrResponse()->getBody();
        $out = @fopen($zipAbs, 'w');
        if (!$out) {
            $this->error('Cannot write to: ' . $zipAbs);
            return self::FAILURE;
        }

        while (!$bodyStream->eof()) {
            fwrite($out, $bodyStream->read(1024 * 1024)); // 1MB chunks
        }
        fclose($out);

        $size = @filesize($zipAbs) ?: 0;
        if ($size < 200 * 1024) { // too small
            $this->error('Downloaded zip looks too small (' . $size . ' bytes). Aborting.');
            return self::FAILURE;
        }
        $this->info('Downloaded (' . $this->humanBytes($size) . ')');

        // 8) Extract zip safely
        $this->info('Extracting...');
        $zip = new ZipArchive();
        $ok = $zip->open($zipAbs);

        if ($ok !== true) {
            $this->error('Failed to open zip (ZipArchive code: ' . $ok . ')');
            return self::FAILURE;
        }

        // Zip slip protection
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) continue;

            $norm = str_replace('\\', '/', $name);
            if (Str::contains($norm, ['../', '..\\']) || str_starts_with($norm, '/')) {
                $zip->close();
                $this->error('Zip contains unsafe path: ' . $name);
                return self::FAILURE;
            }
        }

        if (!$zip->extractTo($extractToAbs)) {
            $zip->close();
            $this->error('Extraction failed.');
            return self::FAILURE;
        }
        $zip->close();

        // 9) Fix nested folder issue: ipdata/ipdata/*
        $this->flattenIfNested($extractToAbs);

        // 10) Validate expected structure
        if (!$this->validateInstalledAbs($extractToAbs)) {
            $this->error('Installed, but structure is still not valid.');
            $this->line('Expected:');
            $this->line('- ' . $extractToAbs . '/countries/*.zone');
            $this->line('- ' . $extractToAbs . '/asn/ip2asn-v4.tsv');
            $this->line('- ' . $extractToAbs . '/countries.php');
            return self::FAILURE;
        }

        // 11) optionally delete zip
        if (!(bool) $this->option('keep-zip')) {
            @unlink($zipAbs);
        }

        // 12) clear cache keys? (optional - recommended)
        $this->warn('Tip: clear app cache if you previously cached null lookups: php artisan optimize:clear');

        $this->info('✅ IP data installed successfully.');
        $this->line('Disk: ' . $disk);
        $this->line('Path: ' . $installRel);

        return self::SUCCESS;
    }

    protected function alreadyInstalled(string $disk, string $installRel): bool
    {
        $base = Storage::disk($disk)->path($installRel);
        return $this->validateInstalledAbs($base);
    }

    protected function validateInstalledAbs(string $baseAbs): bool
    {
        $zones = glob(rtrim($baseAbs, DIRECTORY_SEPARATOR) . '/countries/*.zone') ?: [];
        $asnV4 = rtrim($baseAbs, DIRECTORY_SEPARATOR) . '/asn/ip2asn-v4.tsv';
        $meta  = rtrim($baseAbs, DIRECTORY_SEPARATOR) . '/countries.php';

        return count($zones) > 0 && is_file($asnV4) && is_file($meta);
    }

    /**
     * If zip extracted into: {base}/ipdata/* then move contents up to {base}/*
     */
    protected function flattenIfNested(string $baseAbs): void
    {
        $nested = rtrim($baseAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ipdata';

        // If nested contains expected structure, flatten it.
        if (is_dir($nested) && (is_dir($nested . '/countries') || is_dir($nested . '/asn'))) {
            $this->warn('Detected nested "ipdata/" folder. Flattening...');

            $items = scandir($nested) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $from = $nested . DIRECTORY_SEPARATOR . $item;
                $to   = rtrim($baseAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

                // If target exists, remove it first
                if (file_exists($to)) {
                    $this->rrmdir($to);
                }

                @rename($from, $to);
            }

            // remove empty nested dir
            @rmdir($nested);
        }
    }

    protected function wipeInstallDir(string $disk, string $installRel, string $keepFile): void
    {
        $base = Storage::disk($disk)->path($installRel);
        if (!is_dir($base)) return;

        $items = scandir($base) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === $keepFile) continue;
            $full = $base . DIRECTORY_SEPARATOR . $item;
            $this->rrmdir($full);
        }
    }

    protected function rrmdir(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) return;

        $files = scandir($path) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $this->rrmdir($path . DIRECTORY_SEPARATOR . $f);
        }
        @rmdir($path);
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B','KB','MB','GB','TB'];
        $i = 0;
        $v = (float) $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . ' ' . $units[$i];
    }
}
