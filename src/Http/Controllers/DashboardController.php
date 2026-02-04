<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kianisanaullah\TrafficSentinel\Services\TrafficStats;
use Kianisanaullah\TrafficSentinel\Services\TrafficStatsRange;

class DashboardController extends Controller
{
    public function index(Request $request, TrafficStats $todayStats, TrafficStatsRange $rangeStats)
    {
        $range = $request->get('range', 'today'); // today|7|30
        $days = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        $onlineHumans = $todayStats->onlineHumansCount();
        $onlineBots = $todayStats->onlineBotsCount();

        $data = $days === 1
            ? [
                'unique_humans' => $todayStats->uniqueHumansToday(),
                'unique_bots' => $todayStats->uniqueBotsToday(),
                'pageviews_humans' => $todayStats->pageviewsToday(false),
                'pageviews_all' => $todayStats->pageviewsToday(true),
                'top_pages_humans' => $todayStats->topPagesToday(10, false),
                'top_bots' => $todayStats->topBotsToday(10),
                'top_referrers' => $todayStats->topReferrersToday(10, false),
            ]
            : [
                'unique_humans' => $rangeStats->uniqueHumansLastDays($days),
                'unique_bots' => $rangeStats->uniqueBotsLastDays($days),
                'pageviews_humans' => $rangeStats->pageviewsHumansLastDays($days),
                'pageviews_all' => $rangeStats->pageviewsAllLastDays($days),
                'top_pages_humans' => $rangeStats->topPagesHumansLastDays($days, 10),
                'top_bots' => $rangeStats->topBotsLastDays($days, 10),
                'top_referrers' => $rangeStats->topReferrersHumansLastDays($days, 10),
            ];

        return view('traffic-sentinel::dashboard', [
            'range' => $range,
            'days' => $days,
            'onlineHumans' => $onlineHumans,
            'onlineBots' => $onlineBots,
            'data' => $data,
        ]);
    }
}
