<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotRuleService;

class BotController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $statusFilter = trim((string) $request->get('status', ''));
        $days = (int) $request->get('days', 15);

        if ($days <= 0) {
            $days = 15;
        }

        if ($days > 90) {
            $days = 90;
        }

        $limitDate = now()->subDays($days);

        $bots = DB::table('traffic_sessions_bots')
            ->selectRaw('
            COALESCE(bot_name, "unknown") as bot_name,
            COUNT(*) as sessions,
            COUNT(DISTINCT ip) as ips,
            MAX(last_seen_at) as last_seen
        ')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('bot_name', 'like', '%' . $q . '%')
                        ->orWhereNull('bot_name');
                });
            })
            ->groupBy('bot_name')
            ->orderByDesc('sessions')
            ->paginate(50)
            ->withQueryString();

        $botNames = collect($bots->items())
            ->pluck('bot_name')
            ->filter()
            ->values();

        $rules = DB::table('traffic_bot_rules')
            ->whereIn('bot_name', $botNames)
            ->select(
                'bot_name',
                'action',
                'limit_per_minute',
                'limit_per_hour',
                'limit_per_day'
            )
            ->get()
            ->keyBy('bot_name');

        if ($statusFilter !== '') {
            $bots->setCollection(
                $bots->getCollection()->filter(function ($bot) use ($rules, $statusFilter) {
                    $rule = $rules[$bot->bot_name] ?? null;

                    $status = !$rule
                        ? 'unconfigured'
                        : ($rule->action === 'block'
                            ? 'block'
                            : ($rule->action === 'throttle' ? 'throttle' : 'monitor'));

                    return $status === $statusFilter;
                })->values()
            );
        }

        return view('traffic-sentinel::bots.index', compact('bots', 'rules', 'days'));
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
