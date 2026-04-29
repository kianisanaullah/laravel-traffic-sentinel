<?php

namespace Kianisanaullah\TrafficSentinel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotProtectionService;
use Kianisanaullah\TrafficSentinel\Services\WhitelistIPService;
use Kianisanaullah\TrafficSentinel\Services\CacheService;
use Illuminate\Support\Facades\DB;

class TrackTraffic
{
    protected $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!config('traffic-sentinel.enabled')) {
            return $next($request);
        }

        if (app()->runningInConsole()) {
            return $next($request);
        }

        if (str_contains($request->path(), 'captcha')) {
            return $next($request);
        }

        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        $hasUserAgentHeader = $request->headers->has('User-Agent');
        $userAgent = $request->header('User-Agent');

        if (!$hasUserAgentHeader || trim((string)$userAgent) === '') {
            $ip = app(TrafficTracker::class)->ipForStorage($request->ip());
            $this->logBlockedAttempt($ip, $request, 'empty_user_agent');
            $this->blockIp($ip);
            abort(403, 'Access denied');
        }

        if (!config('traffic-sentinel.track_ajax', true) && $this->isAjaxLike($request)) {
            return $next($request);
        }

        if (!config('traffic-sentinel.track_livewire', true) && $this->isLivewire($request)) {
            return $next($request);
        }

        [$visitorId, $isNewVisitorId] = $this->getOrCreateVisitorId($request);

        $tracker = app(TrafficTracker::class);

        $ipStored = $tracker->ipForStorage($request->ip());
        $blockedKey = $this->blockedIpKey($ipStored);

        if ($this->cache->has($blockedKey)) {

            $this->logBlockedAttempt($ipStored, $request, 'ip_already_blocked');

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

        $rule = $this->cachedRuleLookup($botName, $ipStored, $host, $app);

        if ($rule) {
            if ($rule->action === 'block') {

                $this->logBlockedAttempt($ipStored, $request, 'bot_rule_block', $botName);

                $this->blockIp($ipStored);

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

        return $this->cache->remember($cacheKey, 60, function () use ($botName, $ipStored, $host, $app) {
            return app(BotProtectionService::class)->check($botName, $ipStored, $host, $app);
        });
    }

    private function monitorEveryIp(?string $ipStored, ?string $botName, ?string $host): void
    {
        if (!config('traffic-sentinel.alerts.enabled')) return;
        if (!$ipStored) return;

        $threshold = (int)config('traffic-sentinel.alerts.threshold', 0);
        $window = (int)config('traffic-sentinel.alerts.window_seconds', 60);

        if ($threshold <= 0 || $window <= 0) return;

        $monitorKey = 'ts_alert_monitor_ip:' . $ipStored;

        $hits = $this->cache->increment($monitorKey, ceil($window / 60));

        if ($hits < $threshold) return;

        $this->markCaptchaRequired($ipStored);

        $cooldownKey = 'ts_alert_sent:' . $monitorKey;

        if ($this->cache->has($cooldownKey)) return;

        $this->cache->put($cooldownKey, true, 10);

        $this->sendAlertSafely([
            'key' => $monitorKey,
            'hits' => $hits,
            'ip' => $ipStored,
            'bot_name' => $botName,
            'host' => $host,
        ]);
    }

    private function checkThrottleWindow(string $key, int $limit, int $ttlSeconds, string $message): void
    {
        $hits = $this->cache->increment($key, ceil($ttlSeconds / 60));

        /*
        |--------------------------------------------------------------------------
        | 🔥 ALERT LOGIC (ADD THIS)
        |--------------------------------------------------------------------------
        */
        if (config('traffic-sentinel.alerts.enabled')) {

            $threshold = (int) config('traffic-sentinel.alerts.threshold', 100);

            // trigger only once (prevent spam)
            if ($hits == $threshold) {

                $this->sendAlertSafely(request()->ip(), [
                    'hits' => $hits,
                    'key'  => $key,
                    'time' => now(),
                    'host' => request()->getHost(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 🔥 BLOCK
        |--------------------------------------------------------------------------
        */
        if ($hits > $limit) {
            abort(429, $message);
        }
    }

    private function handleCaptchaChallenge(Request $request, ?string $ipStored)
    {
        if (!config('traffic-sentinel.captcha.enabled', true)) return null;
        if (!$ipStored) return null;

        if (!$this->cache->has($this->captchaRequiredKey($ipStored))) return null;
        if ($this->cache->has($this->captchaPassedKey($ipStored))) return null;

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
        if (!config('traffic-sentinel.captcha.enabled', true)) return;
        if (!$ipStored) return;

        $this->cache->put(
            $this->captchaRequiredKey($ipStored),
            true,
            (int)config('traffic-sentinel.captcha.challenge_minutes', 10)
        );
    }

    private function blockIp(string $ipStored): void
    {
        $this->cache->put(
            $this->blockedIpKey($ipStored),
            true,
            (int)config('traffic-sentinel.block.hours', 24) * 60
        );
    }

    private function logBlockedAttempt(string $ipStored, Request $request, ?string $reason = null, ?string $botName = null): void
    {
        try {

            $logKey = 'ts_block_log:' . $ipStored;

            if ($this->cache->has($logKey)) return;

            $this->cache->put($logKey, true, 1);

            $this->cache->increment('ts_block_hits:' . $ipStored, 10);

            DB::table('traffic_blocked_attempts')->insert([
                'ip' => $ipStored,
                'bot_name' => $botName,
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'host' => $request->getHost(),
                'reason' => $reason,
                'hits' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Throwable $e) {
            \Log::error('TrafficSentinel blocked logging failed', [
                'error' => $e->getMessage(),
            ]);
        }
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

    // ===== REMAINING METHODS (UNCHANGED) =====

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

        $hosts = $cfg['hosts'] ?? [];

        if (is_string($hosts)) {
            $decoded = json_decode($hosts, true);
            $hosts = is_array($decoded) ? $decoded : explode(',', $hosts);
        }

        foreach ($hosts as $h) {
            if ($host === strtolower($h)) {
                return true;
            }
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
    private function sendAlertSafely($ip, $data = [])
    {
        try {

            $emails = config('traffic-sentinel.alerts.email', []);

            // 🔥 Normalize emails
            if (is_string($emails)) {

                // try JSON decode first
                $decoded = json_decode($emails, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $emails = $decoded;
                } else {
                    // fallback: comma separated
                    $emails = array_map('trim', explode(',', $emails));
                }
            }

            if (empty($emails)) {
                return; // no recipients
            }
            \Mail::to($emails)
                ->send(new \App\Mail\TrafficAlertMail($ip, $data));

        } catch (\Throwable $e) {

            \Log::channel('traffic_sentinel')->error('Alert failed', [
                'ip'    => $ip,
                'data'  => $data,
                'error' => $e->getMessage()
            ]);
        }
    }
}
