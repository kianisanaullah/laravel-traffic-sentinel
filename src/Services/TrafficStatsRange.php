<?php

namespace Kianisanaullah\TrafficSentinel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;

class TrafficStatsRange
{
    public function uniqueHumansLastDays(int $days): int
    {
        return $this->cached("uniqueHumansLastDays:$days", 60, function () use ($days) {
            [$start, $end] = $this->range($days);
            return (int) TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', false)
                ->distinct('visitor_key')
                ->count('visitor_key');
        });
    }

    public function uniqueBotsLastDays(int $days): int
    {
        return $this->cached("uniqueBotsLastDays:$days", 60, function () use ($days) {
            [$start, $end] = $this->range($days);
            return (int) TrafficSession::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', true)
                ->distinct('visitor_key')
                ->count('visitor_key');
        });
    }

    public function pageviewsHumansLastDays(int $days): int
    {
        return $this->cached("pageviewsHumansLastDays:$days", 60, function () use ($days) {
            [$start, $end] = $this->range($days);
            return (int) TrafficPageview::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->where('is_bot', false)
                ->count();
        });
    }

    public function pageviewsAllLastDays(int $days): int
    {
        return $this->cached("pageviewsAllLastDays:$days", 60, function () use ($days) {
            [$start, $end] = $this->range($days);
            return (int) TrafficPageview::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->count();
        });
    }

    public function topPagesHumansLastDays(int $days, int $limit = 10): array
    {
        return $this->cached("topPagesHumansLastDays:$days:$limit", 120, function () use ($days, $limit) {
            [$start, $end] = $this->range($days);
            return TrafficPageview::query()
                ->select('path', DB::raw('COUNT(*) as hits'))
                ->whereBetween('viewed_at', [$start, $end])
                ->where('is_bot', false)
                ->groupBy('path')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['path' => $r->path, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    public function topBotsLastDays(int $days, int $limit = 10): array
    {
        return $this->cached("topBotsLastDays:$days:$limit", 120, function () use ($days, $limit) {
            [$start, $end] = $this->range($days);
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

    public function topReferrersHumansLastDays(int $days, int $limit = 10): array
    {
        return $this->cached("topReferrersHumansLastDays:$days:$limit", 180, function () use ($days, $limit) {
            [$start, $end] = $this->range($days);
            return TrafficSession::query()
                ->select('referrer', DB::raw('COUNT(*) as hits'))
                ->whereBetween('first_seen_at', [$start, $end])
                ->where('is_bot', false)
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '')
                ->groupBy('referrer')
                ->orderByDesc('hits')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['referrer' => $r->referrer, 'hits' => (int) $r->hits])
                ->all();
        });
    }

    protected function range(int $days): array
    {
        $end = Carbon::now();
        $start = Carbon::now()->subDays($days)->startOfDay();
        return [$start, $end];
    }

    protected function cached(string $key, int $ttlSeconds, \Closure $fn)
    {
        $enabled = (bool) config('traffic-sentinel.cache.enabled', true);
        $prefix = (string) config('traffic-sentinel.cache.prefix', 'traffic_sentinel:');

        if (! $enabled) return $fn();

        return Cache::remember($prefix.$key, $ttlSeconds, $fn);
    }
}
