<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;

class TrafficStatsRange
{
    public function uniqueHumansLastDays(int $days, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueHumansLastDays:$days:$hostKey:$appKeyK", 60, function () use ($days, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', false);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->distinct('visitor_key')->count('visitor_key');
        });
    }

    public function uniqueBotsLastDays(int $days, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueBotsLastDays:$days:$hostKey:$appKeyK", 60, function () use ($days, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', true);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->distinct('visitor_key')->count('visitor_key');
        });
    }

    /**
     * Unique IPs last N days (Humans)
     * Uses traffic_sessions.ip (hashed/full) so it works with privacy mode too.
     */
    public function uniqueIpsHumansLastDays(int $days, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueIpsHumansLastDays:$days:$hostKey:$appKeyK", 90, function () use ($days, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', false)
                ->whereNotNull('ip')
                ->where('ip', '!=', '');

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->distinct('ip')->count('ip');
        });
    }

    /**
     * Unique IPs last N days (Bots)
     */
    public function uniqueIpsBotsLastDays(int $days, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueIpsBotsLastDays:$days:$hostKey:$appKeyK", 90, function () use ($days, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', true)
                ->whereNotNull('ip')
                ->where('ip', '!=', '');

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->distinct('ip')->count('ip');
        });
    }

    public function pageviewsHumansLastDays(int $days, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("pageviewsHumansLastDays:$days:$hostKey:$appKeyK", 60, function () use ($days, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficPageview::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->where('is_bot', false);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->count();
        });
    }

    public function pageviewsAllLastDays(int $days, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("pageviewsAllLastDays:$days:$hostKey:$appKeyK", 60, function () use ($days, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficPageview::query()
                ->whereBetween('viewed_at', [$start, $end]);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->count();
        });
    }

    public function topPagesHumansLastDays(int $days, int $limit = 10, ?string $host = null, ?string $appKey = null): array
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("topPagesHumansLastDays:$days:$limit:$hostKey:$appKeyK", 120, function () use ($days, $limit, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficPageview::query()
                ->select('path', DB::raw('COUNT(*) as hits'))
                ->whereBetween('viewed_at', [$start, $end])
                ->where('is_bot', false);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return $q->groupBy('path')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['path' => $r->path, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    public function topBotsLastDays(int $days, int $limit = 10, ?string $host = null, ?string $appKey = null): array
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("topBotsLastDays:$days:$limit:$hostKey:$appKeyK", 120, function () use ($days, $limit, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficPageview::query()
                ->select('bot_name', DB::raw('COUNT(*) as hits'))
                ->whereBetween('viewed_at', [$start, $end])
                ->where('is_bot', true);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return $q->groupBy('bot_name')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['bot' => $r->bot_name ?: 'unknown', 'hits' => (int) $r->hits])
                ->all();
        });
    }

    public function topReferrersHumansLastDays(int $days, int $limit = 10, ?string $host = null, ?string $appKey = null): array
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("topReferrersHumansLastDays:$days:$limit:$hostKey:$appKeyK", 180, function () use ($days, $limit, $host, $appKey) {
            [$start, $end] = $this->range($days);

            $q = TrafficSession::query()
                ->select('referrer', DB::raw('COUNT(*) as hits'))
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', false)
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '');

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return $q->groupBy('referrer')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['referrer' => $r->referrer, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    protected function range(int $days): array
    {
        $end   = Carbon::now();
        $start = Carbon::now()->subDays($days)->startOfDay();
        return [$start, $end];
    }

    protected function cached(string $key, int $ttlSeconds, \Closure $fn)
    {
        $enabled = (bool) config('traffic-sentinel.cache.enabled', true);
        $prefix  = (string) config('traffic-sentinel.cache.prefix', 'traffic_sentinel:');

        if (! $enabled) return $fn();

        return Cache::remember($prefix.$key, $ttlSeconds, $fn);
    }
}
