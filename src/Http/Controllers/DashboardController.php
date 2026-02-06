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

        return view('traffic-sentinel::dashboard', [
            'range'        => $range,
            'days'         => $days,

            'hosts'        => $hosts,
            'selectedHost' => $selectedHost,

            'apps'         => $apps,
            'selectedApp'  => $selectedApp,

            'onlineHumans' => $onlineHumans,
            'onlineBots'   => $onlineBots,

            'data'         => $data,
        ]);
    }
}
