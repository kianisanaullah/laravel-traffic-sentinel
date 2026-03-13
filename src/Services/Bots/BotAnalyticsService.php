<?php

namespace Kianisanaullah\TrafficSentinel\Services\Bots;

use Illuminate\Support\Facades\DB;

class BotAnalyticsService
{
    protected function db()
    {
        return \DB::connection(
            config('traffic-sentinel.database.connection', config('database.default'))
        );
    }
    public function topBots($limit = 20)
    {
        return $this->db()->table('traffic_sessions_bots')
            ->selectRaw('bot_name, COUNT(*) as sessions, COUNT(DISTINCT ip) as ips')
            ->groupBy('bot_name')
            ->orderByDesc('sessions')
            ->limit($limit)
            ->get();
    }

    public function botPages($botName, $limit = 50)
    {
        return $this->db()->table('traffic_pageviews_bots')
            ->where('bot_name', $botName)
            ->selectRaw('path, COUNT(*) as visits')
            ->groupBy('path')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();
    }

    public function botIps($botName)
    {
        return $this->db()->table('traffic_pageviews_bots')
            ->where('bot_name', $botName)
            ->selectRaw('ip, COUNT(*) as hits')
            ->groupBy('ip')
            ->orderByDesc('hits')
            ->get();
    }
}
