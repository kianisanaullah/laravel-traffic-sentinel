<?php

namespace Kianisanaullah\TrafficSentinel\Support;

class UserAgentParser
{
    public static function parse(?string $ua): array
    {
        $ua = trim((string) $ua);
        $u  = strtolower($ua);

        if ($ua === '') {
            return self::empty();
        }

        $isBot = self::isBot($u);
        $botName = $isBot ? (self::botName($u) ?? 'bot') : null;

        $device = self::deviceType($u, $isBot);
        $os = self::os($u);
        [$browser, $browserVersion] = self::browser($u, $ua);

        return [
            'is_bot' => $isBot,
            'bot_name' => $botName,

            'device_type' => $device,              // desktop|mobile|tablet|bot|unknown
            'os' => $os['name'],                    // Windows|macOS|iOS|Android|Linux|Unknown
            'os_version' => $os['version'],         // 10|11|17.1|13|etc

            'browser' => $browser,                  // Chrome|Safari|Firefox|Edge|Opera|IE|Unknown
            'browser_version' => $browserVersion,   // 120.0.0.0 etc

            'ua' => $ua,
        ];
    }

    protected static function empty(): array
    {
        return [
            'is_bot' => false,
            'bot_name' => null,
            'device_type' => 'unknown',
            'os' => 'Unknown',
            'os_version' => null,
            'browser' => 'Unknown',
            'browser_version' => null,
            'ua' => '',
        ];
    }

    protected static function isBot(string $u): bool
    {
        // strong signals
        $needles = [
            'bot', 'spider', 'crawler', 'crawl', 'slurp', 'bingpreview',
            'facebookexternalhit', 'facebot', 'twitterbot', 'discordbot',
            'whatsapp', 'telegrambot', 'pinterestbot', 'embedly',
            'ahrefs', 'semrush', 'mj12bot', 'dotbot', 'yandex', 'baiduspider',
            'uptimerobot', 'statuscake', 'pingdom', 'datadog', 'newrelic',
            'headless', 'phantomjs', 'selenium', 'puppeteer', 'playwright',
            'python-requests', 'curl/', 'wget/', 'httpclient', 'go-http-client',
            'okhttp', 'postmanruntime'
        ];

        foreach ($needles as $n) {
            if (strpos($u, $n) !== false) return true;
        }

        return false;
    }

    protected static function botName(string $u): ?string
    {
        $map = [
            'googlebot' => 'googlebot',
            'bingbot' => 'bingbot',
            'duckduckbot' => 'duckduckgo',
            'yandex' => 'yandex',
            'baiduspider' => 'baidu',
            'facebookexternalhit' => 'facebook',
            'twitterbot' => 'twitter',
            'discordbot' => 'discord',
            'slurp' => 'yahoo',
            'ahrefs' => 'ahrefs',
            'semrush' => 'semrush',
            'mj12bot' => 'majestic',
            'dotbot' => 'dotbot',
            'uptimerobot' => 'uptimerobot',
            'pingdom' => 'pingdom',
            'statuscake' => 'statuscake',
            'headless' => 'headless',
            'python-requests' => 'python-requests',
            'curl/' => 'curl',
            'wget/' => 'wget',
            'postmanruntime' => 'postman',
        ];

        foreach ($map as $needle => $name) {
            if (strpos($u, $needle) !== false) return $name;
        }

        // fallback: if it contains "bot" we can still label
        if (strpos($u, 'bot') !== false) return 'bot';

        return null;
    }

    protected static function deviceType(string $u, bool $isBot): string
    {
        if ($isBot) return 'bot';

        if (strpos($u, 'ipad') !== false || strpos($u, 'tablet') !== false) return 'tablet';

        if (
            strpos($u, 'mobile') !== false ||
            strpos($u, 'iphone') !== false ||
            strpos($u, 'android') !== false
        ) return 'mobile';

        // "Macintosh" and "Windows" and "X11" typically desktop
        if (
            strpos($u, 'windows') !== false ||
            strpos($u, 'macintosh') !== false ||
            strpos($u, 'x11') !== false ||
            strpos($u, 'linux') !== false
        ) return 'desktop';

        return 'unknown';
    }

    protected static function os(string $u): array
    {
        // iOS
        if (preg_match('/iphone os ([0-9_]+)/i', $u, $m) || preg_match('/cpu (?:iphone )?os ([0-9_]+)/i', $u, $m)) {
            return ['name' => 'iOS', 'version' => str_replace('_', '.', $m[1])];
        }

        // iPadOS often looks like Mac OS X but contains iPad
        if (strpos($u, 'ipad') !== false && preg_match('/os ([0-9_]+)/i', $u, $m)) {
            return ['name' => 'iPadOS', 'version' => str_replace('_', '.', $m[1])];
        }

        // Android
        if (preg_match('/android ([0-9.]+)/i', $u, $m)) {
            return ['name' => 'Android', 'version' => $m[1]];
        }

        // Windows
        if (preg_match('/windows nt ([0-9.]+)/i', $u, $m)) {
            $map = [
                '10.0' => '10/11',
                '6.3' => '8.1',
                '6.2' => '8',
                '6.1' => '7',
                '6.0' => 'Vista',
                '5.1' => 'XP',
            ];
            $v = $m[1];
            return ['name' => 'Windows', 'version' => $map[$v] ?? $v];
        }

        // macOS
        if (preg_match('/mac os x ([0-9_]+)/i', $u, $m)) {
            return ['name' => 'macOS', 'version' => str_replace('_', '.', $m[1])];
        }

        // Linux
        if (strpos($u, 'linux') !== false) {
            return ['name' => 'Linux', 'version' => null];
        }

        return ['name' => 'Unknown', 'version' => null];
    }

    protected static function browser(string $u, string $ua): array
    {
        // Order matters!

        // Edge (Chromium)
        if (preg_match('/edg\/([0-9.]+)/i', $ua, $m)) return ['Edge', $m[1]];

        // Opera
        if (preg_match('/opr\/([0-9.]+)/i', $ua, $m)) return ['Opera', $m[1]];
        if (preg_match('/opera\/([0-9.]+)/i', $ua, $m)) return ['Opera', $m[1]];

        // Chrome (must be before Safari)
        if (preg_match('/chrome\/([0-9.]+)/i', $ua, $m) && strpos($u, 'chromium') === false) {
            return ['Chrome', $m[1]];
        }

        // Firefox
        if (preg_match('/firefox\/([0-9.]+)/i', $ua, $m)) return ['Firefox', $m[1]];

        // Safari (after Chrome)
        if (strpos($u, 'safari') !== false && preg_match('/version\/([0-9.]+)/i', $ua, $m)) {
            return ['Safari', $m[1]];
        }

        // IE
        if (preg_match('/msie ([0-9.]+)/i', $ua, $m)) return ['IE', $m[1]];
        if (preg_match('/trident\/.*rv:([0-9.]+)/i', $ua, $m)) return ['IE', $m[1]];

        return ['Unknown', null];
    }

    public static function label(array $p): string
    {
        if (($p['is_bot'] ?? false) && !empty($p['bot_name'])) {
            return ucfirst((string) $p['bot_name']) . ' · Bot';
        }

        $browser = $p['browser'] ?? 'Unknown';
        $bv = $p['browser_version'] ?? null;
        $os = $p['os'] ?? 'Unknown';
        $ov = $p['os_version'] ?? null;
        $device = ucfirst((string)($p['device_type'] ?? 'unknown'));

        $b = $bv ? "{$browser} {$bv}" : $browser;
        $o = $ov ? "{$os} {$ov}" : $os;

        return "{$b} · {$o} · {$device}";
    }
}
