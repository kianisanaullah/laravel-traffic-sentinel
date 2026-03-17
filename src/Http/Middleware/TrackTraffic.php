<?php

namespace Kianisanaullah\TrafficSentinel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotProtectionService;

class TrackTraffic
{
    public function handle(Request $request, Closure $next)
    {
        

        if (!config('traffic-sentinel.enabled')) {
            return $next($request);
        }

        if (app()->runningInConsole()) {
            return $next($request);
        }

        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        if (!config('traffic-sentinel.track_ajax', true) && $this->isAjaxLike($request)) {
            return $next($request);
        }

        if (!config('traffic-sentinel.track_livewire', true) && $this->isLivewire($request)) {
            return $next($request);
        }

        [$visitorId, $isNewVisitorId] = $this->getOrCreateVisitorId($request);

        /** @var TrafficTracker $tracker */
        $tracker = app(TrafficTracker::class);

        $ipStored = $tracker->ipForStorage($request->ip());
        $blockedKey = $this->blockedIpKey($ipStored);

        if (Cache::has($blockedKey)) {
            abort(403, 'Your IP has been blocked by Traffic Sentinel.');
        }
        $host = strtolower((string)$request->getHost());
        $app = config('traffic-sentinel.tracking.app_key');

        [$isBot, $botName] = $tracker->detectBotFromRequest($request);
        $captchaRedirect = $this->handleCaptchaChallenge($request, $ipStored);
        if ($captchaRedirect) {
            return $captchaRedirect;
        }

        $this->monitorEveryIp($ipStored, $botName, $host);

        // Cached rule lookup to reduce DB pressure
        $rule = $this->cachedRuleLookup($botName, $ipStored, $host, $app);

        if ($rule) {
            if ($rule->action === 'block') {
                abort(403, 'Blocked');
            }

            if ($rule->action === 'throttle') {
                $this->enforceThrottle($ipStored, $botName, $host, $app, $rule);
            }
        }

        $start = microtime(true);

        $response = $next($request);

        $durationMs = (int)round((microtime(true) - $start) * 1000);

        if ($isNewVisitorId) {
            $this->attachVisitorCookie($request, $response, $visitorId);
        }

        try {
            $status = method_exists($response, 'getStatusCode')
                ? (int)$response->getStatusCode()
                : null;

            if ($status !== null) {
                if (!config('traffic-sentinel.track_redirects', true) && $status >= 300 && $status < 400) {
                    return $response;
                }

                if (!config('traffic-sentinel.track_errors', true) && $status >= 400) {
                    return $response;
                }
            }

            $tracker->track($request, $durationMs, $status, $visitorId);
        } catch (\Throwable $e) {
            \Log::error('TrafficSentinel track failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $response;
    }

    private function cachedRuleLookup(?string $botName, ?string $ipStored, ?string $host, ?string $app)
    {
        $cacheKey = 'ts_rule_lookup:' . md5(json_encode([
                'bot' => $botName,
                'ip' => $ipStored,
                'host' => $host,
                'app' => $app,
            ]));

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($botName, $ipStored, $host, $app) {
            return app(BotProtectionService::class)->check($botName, $ipStored, $host, $app);
        });
    }

    private function monitorEveryIp(?string $ipStored, ?string $botName, ?string $host): void
    {
        if (!config('traffic-sentinel.alerts.enabled')) {
            return;
        }

        if (!$ipStored) {
            return;
        }

        $threshold = (int)config('traffic-sentinel.alerts.threshold', 0);
        $window = (int)config('traffic-sentinel.alerts.window_seconds', 60);

        if ($threshold <= 0 || $window <= 0) {
            return;
        }

        $monitorKey = 'ts_alert_monitor_ip:' . $ipStored;

        if (!Cache::has($monitorKey)) {
            Cache::put($monitorKey, 0, now()->addSeconds($window));
        }

        $hits = Cache::increment($monitorKey);

        // Only cheap cache checks here
        if ($hits < $threshold) {
            return;
        }
        $this->markCaptchaRequired($ipStored);

        $cooldownKey = 'ts_alert_sent:' . $monitorKey;

        if (Cache::has($cooldownKey)) {
            return;
        }

        Cache::put($cooldownKey, true, now()->addMinutes(10));

        // Defer alert send as much as possible
        $this->sendAlertSafely([
            'key' => $monitorKey,
            'hits' => $hits,
            'ip' => $ipStored,
            'bot_name' => $botName,
            'host' => $host,
        ]);
    }

    private function sendAlertSafely(array $data): void
    {
        $emails = collect(explode(',', (string)config('traffic-sentinel.alerts.email', '')))
            ->map(fn($e) => trim($e))
            ->filter()
            ->values()
            ->all();

        if (empty($emails)) {
            return;
        }

        try {
            $trafficType = !empty($data['bot_name']) ? 'Bot' : 'Human';

            Mail::send(
                'traffic-sentinel::emails.high-traffic-alert',
                [
                    'ip' => $data['ip'] ?? null,
                    'hits' => $data['hits'] ?? 0,
                    'trafficType' => $trafficType,
                    'botName' => $data['bot_name'] ?? null,
                    'host' => $data['host'] ?? null,
                    'time' => now(),
                ],
                function ($msg) use ($emails, $data) {
                    $msg->to($emails)
                        ->subject('Traffic Sentinel Alert — High Traffic from IP ' . ($data['ip'] ?? 'unknown'));
                }
            );
        } catch (\Throwable $e) {
            \Log::error('TrafficSentinel alert email failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function enforceThrottle(?string $ipStored, ?string $botName, ?string $host, ?string $app, $rule): void
    {
        $baseKey = $this->throttleKey($ipStored, $botName, $host, $app, $rule);

        if (!$baseKey) {
            return;
        }

        if (!empty($rule->limit_per_minute)) {
            $this->checkThrottleWindow($baseKey . ':min', (int)$rule->limit_per_minute, 60, 'Too many requests per minute');
        }

        if (!empty($rule->limit_per_hour)) {
            $this->checkThrottleWindow($baseKey . ':hour', (int)$rule->limit_per_hour, 3600, 'Too many requests per hour');
        }

        if (!empty($rule->limit_per_day)) {
            $this->checkThrottleWindow($baseKey . ':day', (int)$rule->limit_per_day, 86400, 'Too many requests per day');
        }
    }

    private function checkThrottleWindow(string $key, int $limit, int $ttlSeconds, string $message): void
    {
        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addSeconds($ttlSeconds));
        }

        $hits = Cache::increment($key);

        if ($hits > $limit) {
            abort(429, $message);
        }
    }

    private function throttleKey(?string $ipStored, ?string $botName, ?string $host, ?string $app, $rule): ?string
    {
        if (!empty($rule->ip) && $ipStored) {
            return 'ts_rate_ip:' . $ipStored;
        }

        if (!empty($rule->bot_name) && $botName) {
            return 'ts_rate_bot:' . strtolower($botName);
        }

        if (!empty($rule->host) && $host) {
            return 'ts_rate_host:' . strtolower($host);
        }

        if (!empty($rule->app_key) && $app) {
            return 'ts_rate_app:' . strtolower($app);
        }

        return $ipStored ? 'ts_rate_ip:' . $ipStored : null;
    }

    protected function getOrCreateVisitorId(Request $request): array
    {
        $cookieName = (string)config('traffic-sentinel.cookie.name', 'ts_vid');
        $visitorId = (string)$request->cookie($cookieName);

        if ($visitorId === '' || strlen($visitorId) > 80) {
            $visitorId = (string)Str::uuid();
            return [$visitorId, true];
        }

        return [$visitorId, false];
    }

    protected function attachVisitorCookie(Request $request, $response, string $visitorId): void
    {
        $cookieName = (string)config('traffic-sentinel.cookie.name', 'ts_vid');
        $minutes = (int)config('traffic-sentinel.cookie.minutes', 60 * 24 * 365 * 2);
        $path = (string)config('traffic-sentinel.cookie.path', '/');
        $domain = config('traffic-sentinel.cookie.domain', null);
        $httpOnly = (bool)config('traffic-sentinel.cookie.http_only', true);
        $sameSite = (string)config('traffic-sentinel.cookie.same_site', 'Lax');

        $secure = config('traffic-sentinel.cookie.secure', null);
        $secure = $secure === null ? $request->isSecure() : (bool)$secure;

        $cookie = cookie(
            $cookieName,
            $visitorId,
            $minutes,
            $path,
            $domain,
            $secure,
            $httpOnly,
            false,
            $sameSite
        );

        if (method_exists($response, 'headers')) {
            $response->headers->setCookie($cookie);
        }
    }

    protected function shouldExclude(Request $request): bool
    {
        $cfg = config('traffic-sentinel.exclude', []);

        $host = strtolower((string)$request->getHost());
        foreach (($cfg['hosts'] ?? []) as $h) {
            $h = strtolower(trim($h));
            if ($h !== '' && $host === $h) return true;
        }

        $path = ltrim($request->path(), '/');
        foreach (($cfg['paths'] ?? []) as $p) {
            $p = trim($p, '/');
            if ($p !== '' && str_starts_with($path, $p)) return true;
        }

        $ip = (string)$request->ip();
        foreach (($cfg['ips'] ?? []) as $blocked) {
            if ($blocked === $ip) return true;
        }

        $ua = (string)$request->userAgent();
        foreach (($cfg['user_agents'] ?? []) as $needle) {
            if ($needle && stripos($ua, $needle) !== false) return true;
        }

        return false;
    }

    protected function isAjaxLike(Request $request): bool
    {
        if ($request->ajax()) return true;

        $xrw = strtolower((string)$request->header('x-requested-with'));
        if ($xrw === 'xmlhttprequest') return true;

        $accept = strtolower((string)$request->header('accept'));
        if (str_contains($accept, 'application/json')) return true;

        return $request->wantsJson();
    }

    protected function isLivewire(Request $request): bool
    {
        if ($request->hasHeader('x-livewire')) return true;

        $path = ltrim((string)$request->path(), '/');
        return str_starts_with($path, 'livewire/');
    }

    private function handleCaptchaChallenge(Request $request, ?string $ipStored)
    {
        if (!config('traffic-sentinel.captcha.enabled', true)) {
            return null;
        }

        if (!$ipStored) {
            return null;
        }

        $requiredKey = $this->captchaRequiredKey($ipStored);
        $passedKey = $this->captchaPassedKey($ipStored);

        if (!Cache::has($requiredKey)) {
            return null;
        }

        if (Cache::has($passedKey)) {
            return null;
        }

        if (
            $request->is('captcha') ||
            $request->is('captcha/*') ||
            $request->routeIs('traffic-sentinel.captcha.*')
        ) {
            return null;
        }

        session(['traffic_sentinel_intended_url' => $request->fullUrl()]);

        return redirect()->route('traffic-sentinel.captcha');
    }

    private function markCaptchaRequired(?string $ipStored): void
    {
        if (!config('traffic-sentinel.captcha.enabled', true)) {
            return;
        }

        if (!$ipStored) {
            return;
        }

        Cache::put(
            $this->captchaRequiredKey($ipStored),
            true,
            now()->addMinutes((int)config('traffic-sentinel.captcha.challenge_minutes', 10))
        );
    }

    private function captchaRequiredKey(string $ipStored): string
    {
        return 'ts_captcha_required:' . $ipStored;
    }

    private function captchaPassedKey(string $ipStored): string
    {
        return 'ts_captcha_passed:' . $ipStored;
    }
    private function blockedIpKey(string $ipStored): string
    {
        return 'ts_ip_blocked:' . $ipStored;
    }
    private function blockIp(string $ipStored): void
    {
        Cache::put(
            $this->blockedIpKey($ipStored),
            true,
            now()->addHours((int)config('traffic-sentinel.block.hours', 24))
        );
    }
}
