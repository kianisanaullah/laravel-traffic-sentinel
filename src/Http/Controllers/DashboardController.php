<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewHuman;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewBot;
use Kianisanaullah\TrafficSentinel\Models\TrafficSessionHuman;
use Kianisanaullah\TrafficSentinel\Models\TrafficSessionBot;
use Kianisanaullah\TrafficSentinel\Services\CacheService;

class DashboardController extends Controller
{
    public function index(Request $request, CacheService $cache)
    {
        $range = $request->get('range', 'today');
        $days  = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        $selectedHost = trim((string) $request->get('host', '')) ?: null;
        $selectedApp  = trim((string) $request->get('app', '')) ?: null;

        // mode: humans|bots|both (default humans)
        $mode = strtolower((string) $request->get('mode', 'humans'));
        if (!in_array($mode, ['humans', 'bots', 'both'], true)) $mode = 'humans';

        $includeHumans = in_array($mode, ['humans', 'both'], true);
        $includeBots   = in_array($mode, ['bots', 'both'], true);

        $end   = now();
        $start = $days === 1 ? now()->startOfDay() : now()->subDays($days)->startOfDay();

        $hosts = $cache->remember('hosts:list', 60, function () {
            return TrafficPageviewHuman::query()
                ->whereNotNull('host')
                ->where('host', '!=', '')
                ->select('host')
                ->groupBy('host')
                ->orderBy('host')
                ->pluck('host')
                ->all();
        });

        $apps = $cache->remember('apps:list', 60, function () {

            return TrafficPageviewHuman::query()
                ->whereNotNull('app_key')
                ->where('app_key', '!=', '')
                ->select('app_key')
                ->groupBy('app_key')
                ->orderBy('app_key')
                ->pluck('app_key')
                ->all();
        });

        $minutes = (int) config('traffic-sentinel.online_minutes', 5);

        // -------- Online counts --------
        $onlineHumans = 0;
        if ($includeHumans) {
            $onlineHumans = TrafficSessionHuman::query()
                ->where('last_seen_at', '>=', now()->subMinutes($minutes))
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->count();
        }

        $onlineBots = 0;
        if ($includeBots) {
            $onlineBots = TrafficSessionBot::query()
                ->where('last_seen_at', '>=', now()->subMinutes($minutes))
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->count();
        }

        // -------- Pageviews counts --------
        $pageviewsHumans = 0;
        if ($includeHumans) {
            $pageviewsHumans = TrafficPageviewHuman::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->count();
        }

        $pageviewsBots = 0;
        if ($includeBots) {
            $pageviewsBots = TrafficPageviewBot::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->count();
        }

        // -------- Unique visitor keys --------
        $uniqueHumans = 0;
        if ($includeHumans) {
            $uniqueHumans = TrafficSessionHuman::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->distinct('visitor_key')
                ->count('visitor_key');
        }

        $uniqueBots = 0;
        if ($includeBots) {
            $uniqueBots = TrafficSessionBot::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->distinct('visitor_key')
                ->count('visitor_key');
        }

        // -------- Traffic mix (sessions: direct vs external) --------
        // For humans/bots/both we compute separately and then merge
        $direct = 0;
        $external = 0;

        if ($includeHumans) {
            $h = TrafficSessionHuman::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->selectRaw("SUM(referrer IS NULL OR referrer = '') as direct")
                ->selectRaw("SUM(referrer IS NOT NULL AND referrer != '') as external")
                ->first();

            $direct   += (int) ($h->direct ?? 0);
            $external += (int) ($h->external ?? 0);
        }

        if ($includeBots) {
            $b = TrafficSessionBot::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->selectRaw("SUM(referrer IS NULL OR referrer = '') as direct")
                ->selectRaw("SUM(referrer IS NOT NULL AND referrer != '') as external")
                ->first();

            $direct   += (int) ($b->direct ?? 0);
            $external += (int) ($b->external ?? 0);
        }

        // -------- Chart series --------
        $timeFormat = $days === 1 ? '%H:00' : '%Y-%m-%d';

        $hSeries = collect();
        if ($includeHumans) {
            $hSeries = TrafficPageviewHuman::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->selectRaw("DATE_FORMAT(viewed_at, ?) as t", [$timeFormat])
                ->selectRaw("COUNT(*) as c")
                ->groupBy('t')
                ->orderBy('t')
                ->pluck('c', 't');
        }

        $bSeries = collect();
        if ($includeBots) {
            $bSeries = TrafficPageviewBot::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->selectRaw("DATE_FORMAT(viewed_at, ?) as t", [$timeFormat])
                ->selectRaw("COUNT(*) as c")
                ->groupBy('t')
                ->orderBy('t')
                ->pluck('c', 't');
        }

        $labels = collect()
            ->merge($hSeries->keys())
            ->merge($bSeries->keys())
            ->unique()
            ->sort()
            ->values();

        $chartHumans = $labels->map(fn($t) => (int) ($hSeries[$t] ?? 0))->all();
        $chartBots   = $labels->map(fn($t) => (int) ($bSeries[$t] ?? 0))->all();

        // Only send datasets relevant to mode
        $chart = [
            'labels' => $labels->all(),
            'series' => [
                'humans' => $includeHumans ? $chartHumans : [],
                'bots'   => $includeBots ? $chartBots : [],
            ],
            'mix' => [
                'humans_pageviews'   => (int) ($includeHumans ? $pageviewsHumans : 0),
                'bots_pageviews'     => (int) ($includeBots ? $pageviewsBots : 0),
                'direct_sessions'    => (int) $direct,
                'external_sessions'  => (int) $external,
            ],
        ];

        // -------- Tables --------
        $topPagesHumans = [];
        if ($includeHumans) {
            $topPagesHumans = TrafficPageviewHuman::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->selectRaw("path, COUNT(*) as hits")
                ->groupBy('path')
                ->orderByDesc('hits')
                ->limit(10)
                ->get()
                ->map(fn($r) => ['path' => $r->path, 'hits' => (int) $r->hits])
                ->all();
        }

        $topBots = [];
        if ($includeBots) {
            $topBots = TrafficPageviewBot::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->selectRaw("COALESCE(bot_name,'Unknown') as bot, COUNT(*) as hits")
                ->groupBy('bot')
                ->orderByDesc('hits')
                ->limit(10)
                ->get()
                ->map(fn($r) => ['bot' => $r->bot, 'hits' => (int) $r->hits])
                ->all();
        }

        $topReferrersHumans = [];
        if ($includeHumans) {
            $topReferrersHumans = TrafficPageviewHuman::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '')
                ->selectRaw("referrer, COUNT(*) as hits")
                ->groupBy('referrer')
                ->orderByDesc('hits')
                ->limit(10)
                ->get()
                ->map(fn($r) => ['referrer' => $r->referrer, 'hits' => (int) $r->hits])
                ->all();
        }
        // -------- Unique IPs (using sessions tables) --------
        $uniqueIpsHumans = 0;
        if ($includeHumans) {
            $uniqueIpsHumans = TrafficSessionHuman::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->whereNotNull('ip')
                ->where('ip', '!=', '')
                ->distinct('ip')
                ->count('ip');
        }

        $uniqueIpsBots = 0;
        if ($includeBots) {
            $uniqueIpsBots = TrafficSessionBot::query()
                ->whereBetween('first_seen_at', [$start, $end])
                ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
                ->when($selectedApp,  fn($q) => $q->where('app_key', $selectedApp))
                ->whereNotNull('ip')
                ->where('ip', '!=', '')
                ->distinct('ip')
                ->count('ip');
        }

        $data = [
            'unique_humans'     => (int) $uniqueHumans,
            'unique_bots'       => (int) $uniqueBots,
            'pageviews_humans'  => (int) $pageviewsHumans,
            'pageviews_bots'    => (int) $pageviewsBots,
            'pageviews_all'     => (int) (($includeHumans ? $pageviewsHumans : 0) + ($includeBots ? $pageviewsBots : 0)),
            'unique_ips_humans' => (int) $uniqueIpsHumans,
            'unique_ips_bots'   => (int) $uniqueIpsBots,
            'top_pages_humans'  => $topPagesHumans,
            'top_bots'          => $topBots,
            'top_referrers'     => $topReferrersHumans,
        ];

        return view('traffic-sentinel::dashboard', compact(
            'range',
            'days',
            'hosts',
            'selectedHost',
            'apps',
            'selectedApp',
            'onlineHumans',
            'onlineBots',
            'chart',
            'data',
            'mode',
            'includeHumans',
            'includeBots'
        ));
    }
}
