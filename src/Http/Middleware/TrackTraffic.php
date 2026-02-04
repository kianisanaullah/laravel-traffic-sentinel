<?php

namespace Kianisanaullah\TrafficSentinel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;

class TrackTraffic
{
    public function handle(Request $request, Closure $next)
    {

        if ($this->shouldExclude($request)) {
            return $next($request);
        }
        $start = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        try {
            app(TrafficTracker::class)->track(
                $request,
                $durationMs,
                method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null
            );
        } catch (\Throwable $e) {
            // Never break the app due to tracking
        }

        return $response;
    }
    protected function shouldExclude(Request $request): bool
    {
        // Exclude by host
        $host = $request->getHost();
        if (in_array($host, config('traffic-sentinel.exclude.hosts', []), true)) {
            return true;
        }

        // Exclude by route name
        $routeName = optional($request->route())->getName();
        if ($routeName && in_array($routeName, config('traffic-sentinel.exclude.route_names', []), true)) {
            return true;
        }

        // Exclude by path prefix
        $path = ltrim($request->path(), '/');
        foreach (config('traffic-sentinel.exclude.paths', []) as $exclude) {
            $exclude = ltrim($exclude, '/');
            if ($exclude !== '' && str_starts_with($path, $exclude)) {
                return true;
            }
        }

        return false;
    }
}
