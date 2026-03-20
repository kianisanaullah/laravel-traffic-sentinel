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

        $base = DB::table('traffic_sessions_bots')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('bot_name', 'like', "%{$q}%");
            });

        $bots = DB::query()
            ->fromSub(
                $base->selectRaw("
                COALESCE(bot_name,'unknown') as bot_name,
                ip,
                last_seen_at
            "),
                't'
            )
            ->selectRaw("
            bot_name,
            COUNT(*) as sessions,
            COUNT(DISTINCT ip) as ips,
            MAX(last_seen_at) as last_seen
        ")
            ->groupBy('bot_name')
            ->orderByDesc('sessions')
            ->paginate(50)
            ->withQueryString();

        $botNames = $bots->pluck('bot_name');

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
    public function show(Request $request, $bot)
    {
        $bot = $bot === 'unknown' ? null : $bot;

        $days = (int) $request->get('days', 15);
        $host = $request->get('host');

        $limitDate = now()->subDays($days);

        // 🔹 Base filter (REUSABLE)
        $base = DB::table('traffic_sessions_bots')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($host, fn($q) => $q->where('host', $host))
            ->where(function ($q) use ($bot) {
                if ($bot === null) {
                    $q->whereNull('bot_name');
                } else {
                    $q->where('bot_name', $bot);
                }
            });

        // 🔹 Summary
        $summary = (clone $base)
            ->selectRaw("
            COUNT(*) as sessions,
            COUNT(DISTINCT ip) as ips,
            MAX(last_seen_at) as last_seen
        ")
            ->first();

        // 🔹 IPs
        $ips = (clone $base)
            ->selectRaw("
        ip,
        COUNT(*) as sessions,
        MAX(last_seen_at) as last_seen
    ")
            ->groupBy('ip')
            ->orderByDesc('sessions')
            ->paginate(20)
            ->withQueryString();

        $ips->getCollection()->transform(function ($item) {

            if (filter_var($item->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                // /24 subnet
                $parts = explode('.', $item->ip);
                $item->subnet = $parts[0].'.'.$parts[1].'.'.$parts[2].'.0/24';
            } else {
                $item->subnet = 'IPv6';
            }

            return $item;
        });

        $ipList = $ips->pluck('ip');

        // 🔹 Pages (IMPORTANT: also filtered)
        $pages = DB::table('traffic_pageviews_bots')
            ->whereIn('ip', $ipList)
            ->where('viewed_at', '>=', $limitDate)
            ->when($host, fn($q) => $q->where('host', $host))
            ->where(function ($q) use ($bot) {
                if ($bot === null) {
                    $q->whereNull('bot_name');
                } else {
                    $q->where('bot_name', $bot);
                }
            })
            ->selectRaw("
            ip,
            full_url,
            COUNT(*) as visits,
            MAX(viewed_at) as last_visit
        ")
            ->groupBy('ip', 'full_url')
            ->orderByDesc('visits')
            ->get()
            ->groupBy('ip');

        // 🔹 Rule
        $rule = DB::table('traffic_bot_rules')
            ->where('bot_name', $bot)
            ->first();

        return view('traffic-sentinel::bots.show', compact(
            'bot', 'summary', 'ips', 'pages', 'rule', 'days', 'host'
        ));
    }
}
