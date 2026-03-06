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
        if (!config('traffic-sentinel.enabled')) {
            return $next($request);
        }

        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        if (app()->runningInConsole()) {
            return $next($request);
        }

        if (!config('traffic-sentinel.track_ajax', true) && $this->isAjaxLike($request)) {
            return $next($request);
        }

        if (!config('traffic-sentinel.track_livewire', true) && $this->isLivewire($request)) {
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
            $status = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : null;

            if ($status !== null) {
                if (!config('traffic-sentinel.track_redirects', true) && $status >= 300 && $status < 400) {
                    return $response;
                }

                if (!config('traffic-sentinel.track_errors', true) && $status >= 400) {
                    return $response;
                }
            }

            app(TrafficTracker::class)->track(
                $request,
                $durationMs,
                $status,
                $visitorId
            );
        } catch (\Throwable $e) {
            \Log::error('TrafficSentinel track failed', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $response;
    }

    protected function getOrCreateVisitorId(Request $request): array
    {
        $cookieName = (string) config('traffic-sentinel.cookie.name', 'ts_vid');
        $visitorId  = (string) $request->cookie($cookieName);

        if ($visitorId === '' || strlen($visitorId) > 80) {
            $visitorId = (string) Str::uuid();
            return [$visitorId, true];
        }

        return [$visitorId, false];
    }

    protected function attachVisitorCookie(Request $request, $response, string $visitorId): void
    {
        $cookieName = (string) config('traffic-sentinel.cookie.name', 'ts_vid');
        $minutes    = (int) config('traffic-sentinel.cookie.minutes', 60 * 24 * 365 * 2);
        $path       = (string) config('traffic-sentinel.cookie.path', '/');
        $domain     = config('traffic-sentinel.cookie.domain', null);
        $httpOnly   = (bool) config('traffic-sentinel.cookie.http_only', true);
        $sameSite   = (string) config('traffic-sentinel.cookie.same_site', 'Lax');

        $secure = config('traffic-sentinel.cookie.secure', null);
        $secure = $secure === null ? $request->isSecure() : (bool) $secure;

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

        $host = strtolower((string) $request->getHost());
        foreach (($cfg['hosts'] ?? []) as $h) {
            $h = strtolower(trim($h));
            if ($h !== '' && $host === $h) return true;
        }

        $path = ltrim($request->path(), '/');
        foreach (($cfg['paths'] ?? []) as $p) {
            $p = trim($p, '/');
            if ($p !== '' && str_starts_with($path, $p)) return true;
        }

        $ip = (string) $request->ip();
        foreach (($cfg['ips'] ?? []) as $blocked) {
            if ($blocked === $ip) return true;
        }

        $ua = (string) $request->userAgent();
        foreach (($cfg['user_agents'] ?? []) as $needle) {
            if ($needle && stripos($ua, $needle) !== false) return true;
        }

        return false;
    }

    protected function isAjaxLike(Request $request): bool
    {
        if ($request->ajax()) return true;

        $xrw = strtolower((string) $request->header('x-requested-with'));
        if ($xrw === 'xmlhttprequest') return true;

        $accept = strtolower((string) $request->header('accept'));
        if (str_contains($accept, 'application/json')) return true;

        if ($request->wantsJson()) return true;

        return false;
    }

    protected function isLivewire(Request $request): bool
    {
        if ($request->hasHeader('x-livewire')) return true;

        $path = ltrim((string) $request->path(), '/');
        if (str_starts_with($path, 'livewire/')) return true;

        return false;
    }
}
