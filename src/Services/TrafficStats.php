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
    public function onlineCount(?int $minutes = null, bool $includeBots = false): int
    {
        $minutes ??= (int) config('traffic-sentinel.online_minutes', 5);

        return $this->cached("onlineCount:$minutes:$includeBots", 10, function () use ($minutes, $includeBots) {
            $q = TrafficSession::query()
                ->where('last_seen_at', '>=', Carbon::now()->subMinutes($minutes));

            if (! $includeBots) $q->where('is_bot', false);

            return (int) $q->count();
        });
    }

    public function onlineHumansCount(?int $minutes = null): int
    {
        return $this->onlineCount($minutes, false);
    }

    public function onlineBotsCount(?int $minutes = null): int
    {
        return $this->onlineCount($minutes, true) - $this->onlineCount($minutes, false);
    }

    /**
     * Online sessions list (latest).
     */
    public function onlineList(?int $minutes = null, bool $includeBots = false, int $limit = 50)
    {
        $minutes ??= (int) config('traffic-sentinel.online_minutes', 5);

        $q = TrafficSession::query()
            ->where('last_seen_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderByDesc('last_seen_at')
            ->limit($limit);

        if (! $includeBots) $q->where('is_bot', false);

        return $q->get();
    }

    /**
     * Unique visitors today (by visitor_key).
     */
    public function uniqueToday(bool $includeBots = false): int
    {
        return $this->cached("uniqueToday:$includeBots", 30, function () use ($includeBots) {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            $q = TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end]);

            if (! $includeBots) $q->where('is_bot', false);

            return (int) $q->distinct('visitor_key')->count('visitor_key');
        });
    }

    public function uniqueHumansToday(): int
    {
        return $this->uniqueToday(false);
    }

    public function uniqueBotsToday(): int
    {
        // bots unique = includeBots - humans (safe enough for v1)
        $all = $this->uniqueToday(true);
        $hum = $this->uniqueToday(false);
        return max(0, $all - $hum);
    }

    /**
     * Pageviews today
     */
    public function pageviewsToday(bool $includeBots = false): int
    {
        return $this->cached("pageviewsToday:$includeBots", 30, function () use ($includeBots) {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            $q = TrafficPageview::query()->whereBetween('viewed_at', [$start, $end]);
            if (! $includeBots) $q->where('is_bot', false);

            return (int) $q->count();
        });
    }

    /**
     * Top pages today (by path).
     */
    public function topPagesToday(int $limit = 10, bool $includeBots = false): array
    {
        return $this->cached("topPagesToday:$limit:$includeBots", 60, function () use ($limit, $includeBots) {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            $q = TrafficPageview::query()
                ->select('path', DB::raw('COUNT(*) as hits'))
                ->whereBetween('viewed_at', [$start, $end]);

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
    public function topBotsToday(int $limit = 10): array
    {
        return $this->cached("topBotsToday:$limit", 60, function () use ($limit) {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            return TrafficPageview::query()
                ->select('bot_name', DB::raw('COUNT(*) as hits'))
                ->whereBetween('viewed_at', [$start, $end])
                ->where('is_bot', true)
                ->groupBy('bot_name')
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
    public function topReferrersToday(int $limit = 10, bool $includeBots = false): array
    {
        return $this->cached("topReferrersToday:$limit:$includeBots", 120, function () use ($limit, $includeBots) {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            $q = TrafficSession::query()
                ->select('referrer', DB::raw('COUNT(*) as hits'))
                ->whereBetween('first_seen_at', [$start, $end])
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '');

            if (! $includeBots) $q->where('is_bot', false);

            return $q->groupBy('referrer')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['referrer' => $r->referrer, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    /**
     * Small cache helper.
     * TTL seconds are intentionally short (dashboard-ish).
     */
    protected function cached(string $key, int $ttlSeconds, \Closure $fn)
    {
        $enabled = (bool) config('traffic-sentinel.cache.enabled', true);
        $prefix = (string) config('traffic-sentinel.cache.prefix', 'traffic_sentinel:');

        if (! $enabled) return $fn();

        return Cache::remember($prefix.$key, $ttlSeconds, $fn);
    }
}
