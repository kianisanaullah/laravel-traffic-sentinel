<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;

class TrafficStats
{
    /**
     * Online sessions count.
     */
    public function onlineCount(?int $minutes = null, bool $includeBots = false, ?string $host = null, ?string $appKey = null): int
    {
        $minutes ??= (int) config('traffic-sentinel.online_minutes', 5);
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("onlineCount:$minutes:$includeBots:$hostKey:$appKeyK", 10, function () use ($minutes, $includeBots, $host, $appKey) {
            $q = TrafficSession::query()
                ->where('last_seen_at', '>=', Carbon::now()->subMinutes($minutes));

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            if (! $includeBots) $q->where('is_bot', false);

            return (int) $q->count();
        });
    }

    public function onlineHumansCount(?string $host = null, ?string $appKey = null, ?int $minutes = null): int
    {
        return $this->onlineCount($minutes, false, $host, $appKey);
    }

    public function onlineBotsCount(?string $host = null, ?string $appKey = null, ?int $minutes = null): int
    {
        $minutes ??= (int) config('traffic-sentinel.online_minutes', 5);
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("onlineBotsCount:$minutes:$hostKey:$appKeyK", 10, function () use ($minutes, $host, $appKey) {
            $q = TrafficSession::query()
                ->where('last_seen_at', '>=', Carbon::now()->subMinutes($minutes))
                ->where('is_bot', true);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->count();
        });
    }

    /**
     * Online sessions list (latest).
     */
    public function onlineList(?int $minutes = null, bool $includeBots = false, int $limit = 50, ?string $host = null, ?string $appKey = null)
    {
        $minutes ??= (int) config('traffic-sentinel.online_minutes', 5);

        $q = TrafficSession::query()
            ->where('last_seen_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderByDesc('last_seen_at')
            ->limit($limit);

        if ($host)   $q->where('host', $host);
        if ($appKey) $q->where('app_key', $appKey);
        if (! $includeBots) $q->where('is_bot', false);

        return $q->get();
    }

    /**
     * Unique visitors today (by visitor_key).
     */
    public function uniqueToday(bool $includeBots = false, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueToday:$includeBots:$hostKey:$appKeyK", 30, function () use ($includeBots, $host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end]);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);
            if (! $includeBots) $q->where('is_bot', false);

            return (int) $q->distinct('visitor_key')->count('visitor_key');
        });
    }

    public function uniqueHumansToday(?string $host = null, ?string $appKey = null): int
    {
        return $this->uniqueToday(false, $host, $appKey);
    }

    public function uniqueBotsToday(?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueBotsToday:$hostKey:$appKeyK", 30, function () use ($host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', true);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);

            return (int) $q->distinct('visitor_key')->count('visitor_key');
        });
    }

    /**
     * Unique IPs today (Humans)
     */
    public function uniqueIpsTodayHumans(?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueIpsTodayHumans:$hostKey:$appKeyK", 45, function () use ($host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

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
     * Unique IPs today (Bots)
     */
    public function uniqueIpsTodayBots(?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("uniqueIpsTodayBots:$hostKey:$appKeyK", 45, function () use ($host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

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

    /**
     * Pageviews today
     */
    public function pageviewsToday(bool $includeBots = false, ?string $host = null, ?string $appKey = null): int
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("pageviewsToday:$includeBots:$hostKey:$appKeyK", 30, function () use ($includeBots, $host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

            $q = TrafficPageview::query()->whereBetween('viewed_at', [$start, $end]);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);
            if (! $includeBots) $q->where('is_bot', false);

            return (int) $q->count();
        });
    }

    /**
     * Top pages today (by path).
     */
    public function topPagesToday(int $limit = 10, bool $includeBots = false, ?string $host = null, ?string $appKey = null): array
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("topPagesToday:$limit:$includeBots:$hostKey:$appKeyK", 60, function () use ($limit, $includeBots, $host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

            $q = TrafficPageview::query()
                ->select('path', DB::raw('COUNT(*) as hits'))
                ->whereBetween('viewed_at', [$start, $end]);

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);
            if (! $includeBots) $q->where('is_bot', false);

            return $q->groupBy('path')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['path' => $r->path, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    /**
     * Top bots today (by bot_name).
     */
    public function topBotsToday(int $limit = 10, ?string $host = null, ?string $appKey = null): array
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("topBotsToday:$limit:$hostKey:$appKeyK", 60, function () use ($limit, $host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

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

    /**
     * Top referrers today (from session referrer).
     */
    public function topReferrersToday(int $limit = 10, bool $includeBots = false, ?string $host = null, ?string $appKey = null): array
    {
        $hostKey = $host ? strtolower($host) : 'all';
        $appKeyK = $appKey ? strtolower($appKey) : 'all';

        return $this->cached("topReferrersToday:$limit:$includeBots:$hostKey:$appKeyK", 120, function () use ($limit, $includeBots, $host, $appKey) {
            $start = Carbon::today();
            $end   = Carbon::tomorrow();

            $q = TrafficSession::query()
                ->select('referrer', DB::raw('COUNT(*) as hits'))
                ->whereBetween('first_seen_at', [$start, $end])
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '');

            if ($host)   $q->where('host', $host);
            if ($appKey) $q->where('app_key', $appKey);
            if (! $includeBots) $q->where('is_bot', false);

            return $q->groupBy('referrer')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['referrer' => $r->referrer, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    protected function cached(string $key, int $ttlSeconds, \Closure $fn)
    {
        $enabled = (bool) config('traffic-sentinel.cache.enabled', true);
        $prefix  = (string) config('traffic-sentinel.cache.prefix', 'traffic_sentinel:');

        if (! $enabled) return $fn();

        return Cache::remember($prefix.$key, $ttlSeconds, $fn);
    }
}
