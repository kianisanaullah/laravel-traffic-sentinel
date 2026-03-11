<?php

namespace Kianisanaullah\TrafficSentinel\Services\Bots;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BotTrafficService
{
    public function botsLastMinutes($minutes = 5)
    {
        return DB::table('traffic_pageviews_bots')
            ->where('viewed_at', '>=', Carbon::now()->subMinutes($minutes))
            ->selectRaw('bot_name, COUNT(*) as hits')
            ->groupBy('bot_name')
            ->orderByDesc('hits')
            ->get();
    }

    public function requestsPerMinute($botName)
    {
        return DB::table('traffic_pageviews_bots')
            ->where('bot_name', $botName)
            ->where('viewed_at', '>=', now()->subMinute())
            ->count();
    }

    public function requestsPerHour($botName)
    {
        return DB::table('traffic_pageviews_bots')
            ->where('bot_name', $botName)
            ->where('viewed_at', '>=', now()->subHour())
            ->count();
    }
}
