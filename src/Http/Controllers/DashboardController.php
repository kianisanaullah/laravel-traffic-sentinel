<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Services\TrafficStats;
use Kianisanaullah\TrafficSentinel\Services\TrafficStatsRange;

class DashboardController extends Controller
{
    public function index(Request $request, TrafficStats $todayStats, TrafficStatsRange $rangeStats)
    {
        $range = $request->get('range', 'today');
        $days  = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        $selectedHost = trim((string) $request->get('host', ''));
        if ($selectedHost === '') $selectedHost = null;

        $selectedApp = trim((string) $request->get('app', ''));
        if ($selectedApp === '') $selectedApp = null;

        // Hosts dropdown
        $hosts = TrafficPageview::query()
            ->whereNotNull('host')
            ->where('host', '!=', '')
            ->when($selectedApp, fn($q) => $q->where('app_key', $selectedApp))
            ->select('host')
            ->distinct()
            ->orderBy('host')
            ->pluck('host')
            ->values()
            ->all();

        // Apps dropdown (app_key)
        $apps = TrafficPageview::query()
            ->whereNotNull('app_key')
            ->where('app_key', '!=', '')
            ->select('app_key')
            ->distinct()
            ->orderBy('app_key')
            ->pluck('app_key')
            ->values()
            ->all();

        // Online
        $onlineHumans = $todayStats->onlineHumansCount($selectedHost, $selectedApp);
        $onlineBots   = $todayStats->onlineBotsCount($selectedHost, $selectedApp);

        // Stats payload
        $data = $days === 1
            ? [
                'unique_humans'      => $todayStats->uniqueHumansToday($selectedHost, $selectedApp),
                'unique_bots'        => $todayStats->uniqueBotsToday($selectedHost, $selectedApp),

                'unique_ips_humans'  => $todayStats->uniqueIpsTodayHumans($selectedHost, $selectedApp),
                'unique_ips_bots'    => $todayStats->uniqueIpsTodayBots($selectedHost, $selectedApp),

                'pageviews_humans'   => $todayStats->pageviewsToday(false, $selectedHost, $selectedApp),
                'pageviews_all'      => $todayStats->pageviewsToday(true, $selectedHost, $selectedApp),

                'top_pages_humans'   => $todayStats->topPagesToday(10, false, $selectedHost, $selectedApp),
                'top_bots'           => $todayStats->topBotsToday(10, $selectedHost, $selectedApp),
                'top_referrers'      => $todayStats->topReferrersToday(10, false, $selectedHost, $selectedApp),
            ]
            : [
                'unique_humans'      => $rangeStats->uniqueHumansLastDays($days, $selectedHost, $selectedApp),
                'unique_bots'        => $rangeStats->uniqueBotsLastDays($days, $selectedHost, $selectedApp),

                'unique_ips_humans'  => $rangeStats->uniqueIpsHumansLastDays($days, $selectedHost, $selectedApp),
                'unique_ips_bots'    => $rangeStats->uniqueIpsBotsLastDays($days, $selectedHost, $selectedApp),

                'pageviews_humans'   => $rangeStats->pageviewsHumansLastDays($days, $selectedHost, $selectedApp),
                'pageviews_all'      => $rangeStats->pageviewsAllLastDays($days, $selectedHost, $selectedApp),

                'top_pages_humans'   => $rangeStats->topPagesHumansLastDays($days, 10, $selectedHost, $selectedApp),
                'top_bots'           => $rangeStats->topBotsLastDays($days, 10, $selectedHost, $selectedApp),
                'top_referrers'      => $rangeStats->topReferrersHumansLastDays($days, 10, $selectedHost, $selectedApp),
            ];
        $end = now();
        $start = $days === 1 ? now()->startOfDay() : now()->subDays($days)->startOfDay();

        $pvBase = \Kianisanaullah\TrafficSentinel\Models\TrafficPageview::query()
            ->whereBetween('viewed_at', [$start, $end])
            ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
            ->when($selectedApp, fn($q) => $q->where('app_key', $selectedApp));

        $timeFormat = $days === 1 ? '%H:00' : '%Y-%m-%d';

        $pageviewsSeries = (clone $pvBase)
            ->selectRaw("DATE_FORMAT(viewed_at, '{$timeFormat}') as t")
            ->selectRaw("SUM(CASE WHEN is_bot = 0 THEN 1 ELSE 0 END) as humans")
            ->selectRaw("SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bots")
            ->groupBy('t')
            ->orderBy('t')
            ->get();

        $chartLabels = $pageviewsSeries->pluck('t')->values()->all();
        $chartHumans = $pageviewsSeries->pluck('humans')->map(fn($v) => (int)$v)->values()->all();
        $chartBots   = $pageviewsSeries->pluck('bots')->map(fn($v) => (int)$v)->values()->all();

        $directCount = \Kianisanaullah\TrafficSentinel\Models\TrafficSession::query()
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->where(function($q){
                $q->whereNull('referrer')->orWhere('referrer', '');
            })
            ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
            ->when($selectedApp, fn($q) => $q->where('app_key', $selectedApp))
            ->count();

        $externalCount = \Kianisanaullah\TrafficSentinel\Models\TrafficSession::query()
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
            ->when($selectedApp, fn($q) => $q->where('app_key', $selectedApp))
            ->count();

        $humansTotal = (int)($data['pageviews_humans'] ?? 0);
        $botsTotal   = max(0, (int)($data['pageviews_all'] ?? 0) - $humansTotal);

        $chart = [
            'labels' => $chartLabels,
            'series' => [
                'humans' => $chartHumans,
                'bots'   => $chartBots,
            ],
            'mix' => [
                'humans_pageviews' => $humansTotal,
                'bots_pageviews'   => $botsTotal,
                'direct_sessions'  => $directCount,
                'external_sessions'=> $externalCount,
            ],
        ];
        $recentSessions = \Kianisanaullah\TrafficSentinel\Models\TrafficSession::query()
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->when($selectedHost, fn($q) => $q->where('host', $selectedHost))
            ->when($selectedApp, fn($q) => $q->where('app_key', $selectedApp))
            ->orderByDesc('first_seen_at')
            ->limit(12)
            ->get();

        return view('traffic-sentinel::dashboard', [
            'range'        => $range,
            'days'         => $days,

            'hosts'        => $hosts,
            'selectedHost' => $selectedHost,

            'apps'         => $apps,
            'selectedApp'  => $selectedApp,

            'onlineHumans' => $onlineHumans,
            'onlineBots'   => $onlineBots,
            'chart' => $chart,
            'recentSessions' => $recentSessions,
            'data'         => $data,
        ]);
    }
}
