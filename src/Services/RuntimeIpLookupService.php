<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;


class RuntimeIpLookupService
{
    /** @var array<string, array{name:string,flag:string}>|null */
    protected static $countryMeta = null;

    protected string $basePath;

    public function __construct()
    {
        $disk = config('traffic-sentinel.ip_lookup.storage.disk', 'local');
        $path = config('traffic-sentinel.ip_lookup.storage.path', 'traffic-sentinel/ipdata');

        $this->basePath = Storage::disk($disk)->path($path);
    }

    protected function countriesDir(): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'countries';
    }

    protected function asnV4File(): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'asn' . DIRECTORY_SEPARATOR . 'ip2asn-v4.tsv';
    }

    protected function asnV6File(): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'asn' . DIRECTORY_SEPARATOR . 'ip2asn-v6.tsv';
    }

    protected function countriesMetaFile(): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'countries.php';
    }

    public function lookup(?string $ip): array
    {
        $ip = $this->normalizeIp($ip);

        if (!$ip) {
            return [
                'ip' => null,
                'version' => null,
                'country_code' => null,
                'country_name' => null,
                'flag' => null,
                'asn' => null,
                'asn_name' => null,
                'asn_country_code' => null,
            ];
        }

        return Cache::remember(
            'ts:iplookup:' . $ip,
            now()->addHours(12),
            function () use ($ip) {
                $country = $this->lookupCountry($ip);
                $asn     = $this->lookupAsn($ip);

                return [
                    'ip' => $ip,
                    'version' => $this->isV6($ip) ? 6 : 4,

                    'country_code' => $country['country_code'] ?? null,
                    'country_name' => $country['country_name'] ?? null,
                    'flag' => $country['flag'] ?? null,

                    'asn' => $asn['asn'] ?? null,
                    'asn_name' => $asn['asn_name'] ?? null,
                    'asn_country_code' => $asn['asn_country_code'] ?? null,
                ];
            }
        );
    }

    /* ============================================================
     | Country lookup (STREAM zones, stop on first match)
     * ============================================================ */

    public function lookupCountry(string $ip): array
    {
        $ip = $this->normalizeIp($ip);
        if (!$ip) return [];

        $dir = $this->countriesDir();
        if (!is_dir($dir)) return [];

        // Iterate each country file; stream each line
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.zone') ?: [] as $file) {
            $cc = strtoupper(pathinfo($file, PATHINFO_FILENAME));

            $fh = @fopen($file, 'r');
            if (!$fh) continue;

            while (($line = fgets($fh)) !== false) {
                $cidr = trim($line);
                if ($cidr === '' || str_starts_with($cidr, '#')) continue;

                // Skip mismatched family
                if ($this->isV6($ip)) {
                    if (!str_contains($cidr, ':')) continue;
                    if ($this->ipInCidrV6($ip, $cidr)) {
                        fclose($fh);
                        return $this->decorateCountry($cc);
                    }
                } else {
                    if (str_contains($cidr, ':')) continue;
                    if ($this->ipInCidrV4($ip, $cidr)) {
                        fclose($fh);
                        return $this->decorateCountry($cc);
                    }
                }
            }

            fclose($fh);
        }

        return [];
    }

    protected function decorateCountry(string $cc): array
    {
        $meta = $this->getCountryMeta();
        $cc = strtoupper($cc);

        return [
            'country_code' => $cc,
            'country_name' => $meta[$cc]['name'] ?? $cc,
            'flag' => $meta[$cc]['flag'] ?? '🌐',
        ];
    }

    /* ============================================================
     | ASN lookup (STREAM TSV, stop on first match)
     | NOTE: This is safe; later we can optimize with binary search.
     * ============================================================ */

    public function lookupAsn(string $ip): array
    {
        $ip = $this->normalizeIp($ip);
        if (!$ip) return [];

        return $this->isV6($ip)
            ? $this->lookupAsnV6($ip)
            : $this->lookupAsnV4($ip);
    }

    protected function lookupAsnV4(string $ip): array
    {
        $file = $this->asnV4File();
        if (!is_file($file)) return [];

        $ipInt = $this->ip4ToUInt($ip);
        if ($ipInt === null) return [];

        $fh = @fopen($file, 'r');
        if (!$fh) return [];

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            $cols = explode("\t", $line);
            // ip2asn: start end asn country name
            $startRaw = $cols[0] ?? null;
            $endRaw   = $cols[1] ?? null;
            $asn      = (int) ($cols[2] ?? 0);
            $cc       = isset($cols[3]) ? strtoupper(trim($cols[3])) : null;
            $name     = isset($cols[4]) ? trim($cols[4]) : null;

            if (!$startRaw || !$endRaw || $asn <= 0) continue;

            $start = $this->parseV4StartEnd($startRaw);
            $end   = $this->parseV4StartEnd($endRaw);
            if ($start === null || $end === null) continue;

            if ($ipInt >= $start && $ipInt <= $end) {
                fclose($fh);
                return [
                    'asn' => $asn,
                    'asn_country_code' => $cc ?: null,
                    'asn_name' => $name ?: null,
                ];
            }
        }

        fclose($fh);
        return [];
    }

    protected function lookupAsnV6(string $ip): array
    {
        $file = $this->asnV6File();
        if (!is_file($file)) return [];

        $ipBin = @inet_pton($ip);
        if ($ipBin === false || strlen($ipBin) !== 16) return [];

        $fh = @fopen($file, 'r');
        if (!$fh) return [];

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            $cols = explode("\t", $line);
            $startRaw = $cols[0] ?? null;
            $endRaw   = $cols[1] ?? null;
            $asn      = (int) ($cols[2] ?? 0);
            $cc       = isset($cols[3]) ? strtoupper(trim($cols[3])) : null;
            $name     = isset($cols[4]) ? trim($cols[4]) : null;

            if (!$startRaw || !$endRaw || $asn <= 0) continue;

            $startBin = @inet_pton($startRaw);
            $endBin   = @inet_pton($endRaw);

            if ($startBin === false || $endBin === false) continue;

            if (strcmp($ipBin, $startBin) >= 0 && strcmp($ipBin, $endBin) <= 0) {
                fclose($fh);
                return [
                    'asn' => $asn,
                    'asn_country_code' => $cc ?: null,
                    'asn_name' => $name ?: null,
                ];
            }
        }

        fclose($fh);
        return [];
    }

    /* ============================================================
     | CIDR checks (fast, no big arrays)
     * ============================================================ */

    protected function ipInCidrV4(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) return false;
        if ($mask < 0 || $mask > 32) return false;

        $maskLong = $mask === 0 ? 0 : (-1 << (32 - $mask));
        return (($ipLong & $maskLong) === ($subnetLong & $maskLong));
    }

    protected function ipInCidrV6(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int) $mask;

        $ipBin = @inet_pton($ip);
        $subBin = @inet_pton($subnet);

        if ($ipBin === false || $subBin === false) return false;
        if ($mask < 0 || $mask > 128) return false;

        $bytes = intdiv($mask, 8);
        $bits  = $mask % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) return true;

        $ipByte  = ord($ipBin[$bytes]);
        $subByte = ord($subBin[$bytes]);

        $m = (0xFF << (8 - $bits)) & 0xFF;
        return (($ipByte & $m) === ($subByte & $m));
    }

    /* ============================================================
     | Meta + Paths
     * ============================================================ */

    protected function getCountryMeta(): array
    {
        if (self::$countryMeta !== null) return self::$countryMeta;

        $file = $this->countriesMetaFile();
        if (is_file($file)) {
            $arr = require $file;
            if (is_array($arr)) {
                return self::$countryMeta = $arr;
            }
        }
        return self::$countryMeta = [];
    }


    /* ============================================================
     | IP parsing helpers
     * ============================================================ */

    protected function normalizeIp(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        if ($ip === '') return null;

        // Handle ipv4:port
        if (preg_match('/^\d+\.\d+\.\d+\.\d+:\d+$/', $ip)) {
            $ip = explode(':', $ip)[0];
        }

        $bin = @inet_pton($ip);
        if ($bin === false) return null;

        return $this->isV6($ip) ? inet_ntop($bin) : $ip;
    }

    protected function isV6(string $ip): bool
    {
        return str_contains($ip, ':');
    }

    protected function ip4ToUInt(string $ip): ?int
    {
        $long = ip2long($ip);
        if ($long === false) return null;
        return (int) sprintf('%u', $long);
    }

    protected function parseV4StartEnd(string $val): ?int
    {
        $val = trim($val);

        // ip2asn usually uses integer start/end for v4
        if (ctype_digit($val)) {
            return (int) $val;
        }

        // sometimes dotted format
        if (str_contains($val, '.')) {
            return $this->ip4ToUInt($val);
        }

        return null;
    }

    protected function flagEmojiFromCode(string $cc): string
    {
        $cc = strtoupper(trim($cc));
        if (!preg_match('/^[A-Z]{2}$/', $cc)) return '🌐';

        // 🇦 = 0x1F1E6 ... convert A-Z to regional indicator symbols
        $a = ord('A');
        $first  = 0x1F1E6 + (ord($cc[0]) - $a);
        $second = 0x1F1E6 + (ord($cc[1]) - $a);

        return mb_convert_encoding('&#' . $first . ';&#' . $second . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}
