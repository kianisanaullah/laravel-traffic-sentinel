<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotRuleService;

class BotController extends Controller
{
    public function index()
    {
        $bots = DB::table('traffic_sessions_bots')
            ->selectRaw('
        COALESCE(bot_name, "unknown") as bot_name,
        COUNT(*) as sessions,
        COUNT(DISTINCT ip) as ips,
        MAX(last_seen_at) as last_seen
    ')
            ->groupBy('bot_name')
            ->orderByDesc('sessions')
            ->get();

        $rules = DB::table('traffic_bot_rules')
            ->select(
                'bot_name',
                'action',
                'limit_per_minute',
                'limit_per_hour',
                'limit_per_day'
            )
            ->get()
            ->keyBy('bot_name');


        return view('traffic-sentinel::bots.index', compact('bots','rules'));
    }


    public function block(Request $request, BotRuleService $service)
    {
        $service->blockBot($request->bot);

        return back()->with('success','Bot blocked');
    }


    public function throttle(Request $request, BotRuleService $service)
    {
        $rpm = $request->rpm ?? 60;

        $service->throttleBot($request->bot, $rpm);

        return back()->with('success','Bot throttled');
    }


    public function monitor(Request $request, BotRuleService $service)
    {
        $service->monitorBot($request->bot);

        return back()->with('success','Bot set to monitor');
    }
}
