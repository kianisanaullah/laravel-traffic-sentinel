<?php

namespace Kianisanaullah\TrafficSentinel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;

class TrackTraffic
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldExclude($request)) {
            return $next($request);
        }
        [$visitorId, $isNewVisitorId] = $this->getOrCreateVisitorId($request);

        $start = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if ($isNewVisitorId) {
            $this->attachVisitorCookie($request, $response, $visitorId);
        }
        try {
            app(TrafficTracker::class)->track(
                $request,
                $durationMs,
                method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                $visitorId
            );
        } catch (\Throwable $e) {

        }

        return $response;
    }

    /**
     * Returns: [visitorId, isNew]
     */
    protected function getOrCreateVisitorId(Request $request): array
    {
        $cookieName = (string) config('traffic-sentinel.cookie.name', 'ts_vid');

        $visitorId = (string) $request->cookie($cookieName);

        if ($visitorId === '' || strlen($visitorId) > 80) {
            $visitorId = (string) Str::uuid();
            return [$visitorId, true];
        }

        return [$visitorId, false];
    }

    protected function attachVisitorCookie(Request $request, $response, string $visitorId): void
    {
        $cookieName   = (string) config('traffic-sentinel.cookie.name', 'ts_vid');
        $minutes      = (int) config('traffic-sentinel.cookie.minutes', 60 * 24 * 365 * 2);
        $path         = (string) config('traffic-sentinel.cookie.path', '/');
        $domain       = config('traffic-sentinel.cookie.domain', null);
        $httpOnly     = (bool) config('traffic-sentinel.cookie.http_only', true);
        $sameSite     = (string) config('traffic-sentinel.cookie.same_site', 'Lax');

        $secure = config('traffic-sentinel.cookie.secure', null);
        if ($secure === null) {
            $secure = $request->isSecure();
        } else {
            $secure = (bool) $secure;
        }

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

        // Attach to response (supports both normal + symfony responses)
        if (method_exists($response, 'headers')) {
            $response->headers->setCookie($cookie);
        }
    }

    protected function shouldExclude(Request $request): bool
    {
        $cfg = config('traffic-sentinel.exclude', []);

        // Host exclude
        $host = strtolower((string) $request->getHost());
        foreach (($cfg['hosts'] ?? []) as $h) {
            $h = strtolower(trim($h));
            if ($h !== '' && $host === $h) return true;
        }

        // Path exclude
        $path = ltrim($request->path(), '/');
        foreach (($cfg['paths'] ?? []) as $p) {
            $p = trim($p, '/');
            if ($p !== '' && str_starts_with($path, $p)) return true;
        }

        // IP exclude
        $ip = (string) $request->ip();
        foreach (($cfg['ips'] ?? []) as $blocked) {
            if ($blocked === $ip) return true;
        }

        // UA exclude
        $ua = (string) $request->userAgent();
        foreach (($cfg['user_agents'] ?? []) as $needle) {
            if ($needle && stripos($ua, $needle) !== false) return true;
        }

        return false;
    }
}
