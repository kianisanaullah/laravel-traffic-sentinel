<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;
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

        $hosts = TrafficPageview::query()
            ->whereNotNull('host')
            ->where('host', '!=', '')
            ->select('host')
            ->distinct()
            ->orderBy('host')
            ->pluck('host')
            ->values()
            ->all();

        $onlineHumans = $todayStats->onlineHumansCount($selectedHost);
        $onlineBots   = $todayStats->onlineBotsCount($selectedHost);

        $data = $days === 1
            ? [
                'unique_humans'     => $todayStats->uniqueHumansToday($selectedHost),
                'unique_bots'       => $todayStats->uniqueBotsToday($selectedHost),
                'pageviews_humans'  => $todayStats->pageviewsToday(false, $selectedHost),
                'pageviews_all'     => $todayStats->pageviewsToday(true, $selectedHost),
                'top_pages_humans'  => $todayStats->topPagesToday(10, false, $selectedHost),
                'top_bots'          => $todayStats->topBotsToday(10, $selectedHost),
                'top_referrers'     => $todayStats->topReferrersToday(10, false, $selectedHost),
            ]
            : [
                'unique_humans'     => $rangeStats->uniqueHumansLastDays($days, $selectedHost),
                'unique_bots'       => $rangeStats->uniqueBotsLastDays($days, $selectedHost),
                'pageviews_humans'  => $rangeStats->pageviewsHumansLastDays($days, $selectedHost),
                'pageviews_all'     => $rangeStats->pageviewsAllLastDays($days, $selectedHost),
                'top_pages_humans'  => $rangeStats->topPagesHumansLastDays($days, 10, $selectedHost),
                'top_bots'          => $rangeStats->topBotsLastDays($days, 10, $selectedHost),
                'top_referrers'     => $rangeStats->topReferrersHumansLastDays($days, 10, $selectedHost),
            ];

        return view('traffic-sentinel::dashboard', [
            'range'         => $range,
            'days'          => $days,
            'hosts'         => $hosts,
            'selectedHost'  => $selectedHost,
            'onlineHumans'  => $onlineHumans,
            'onlineBots'    => $onlineBots,
            'data'          => $data,
        ]);
    }
}
