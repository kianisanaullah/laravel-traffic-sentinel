<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotRuleService;

class IpRuleController extends Controller
{
    public function index(Request $request)
    {
        $limitDate = now()->subDays(15);

        $ipFilter = trim((string) $request->get('ip', ''));
        $typeFilter = trim((string) $request->get('type', ''));
        $statusFilter = trim((string) $request->get('status', ''));

        $humanIps = DB::table('traffic_sessions_humans')
            ->selectRaw("
            ip,
            'human' as traffic_type,
            COUNT(*) as sessions,
            MAX(last_seen_at) as last_seen
        ")
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($ipFilter !== '', function ($q) use ($ipFilter) {
                $q->where('ip', 'like', '%' . $ipFilter . '%');
            })
            ->groupBy('ip');

        $botIps = DB::table('traffic_sessions_bots')
            ->selectRaw("
            ip,
            'bot' as traffic_type,
            COUNT(*) as sessions,
            MAX(last_seen_at) as last_seen
        ")
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($ipFilter !== '', function ($q) use ($ipFilter) {
                $q->where('ip', 'like', '%' . $ipFilter . '%');
            })
            ->groupBy('ip');

        $ips = DB::query()
            ->fromSub($humanIps->unionAll($botIps), 'x')
            ->selectRaw("
            ip,
            SUM(sessions) as sessions,
            MAX(last_seen) as last_seen,
            CASE
                WHEN SUM(CASE WHEN traffic_type='human' THEN 1 ELSE 0 END) > 0
                 AND SUM(CASE WHEN traffic_type='bot' THEN 1 ELSE 0 END) > 0
                    THEN 'mixed'
                WHEN SUM(CASE WHEN traffic_type='bot' THEN 1 ELSE 0 END) > 0
                    THEN 'bot'
                ELSE 'human'
            END as traffic_type
        ")
            ->groupBy('ip')
            ->when(in_array($typeFilter, ['human', 'bot', 'mixed'], true), function ($q) use ($typeFilter) {
                $q->having('traffic_type', '=', $typeFilter);
            })
            ->orderByDesc('sessions')
            ->paginate(50)
            ->withQueryString();

        $pageIps = collect($ips->items())->pluck('ip')->filter()->values();

        $rules = DB::table('traffic_bot_rules')
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->whereIn('ip', $pageIps)
            ->select('ip', 'action', 'limit_per_minute', 'limit_per_hour', 'limit_per_day')
            ->get()
            ->keyBy('ip');

        if ($statusFilter !== '') {
            $ips->setCollection(
                $ips->getCollection()->filter(function ($row) use ($rules, $statusFilter) {
                    $rule = $rules[$row->ip] ?? null;

                    $status = !$rule
                        ? 'unconfigured'
                        : ($rule->action === 'block'
                            ? 'block'
                            : ($rule->action === 'throttle' ? 'throttle' : 'monitor'));

                    return $status === $statusFilter;
                })->values()
            );
        }

        return view('traffic-sentinel::ips.index', compact('ips', 'rules'));
    }
    public function monitor(Request $request, BotRuleService $service)
    {
        $request->validate([
            'ip_rule' => ['required', 'string', 'max:255'],
        ]);

        $service->monitorIp($request->ip_rule);

        return back()->with('success', 'IP set to monitoring.');
    }

    public function block(Request $request, BotRuleService $service)
    {
        $request->validate([
            'ip_rule' => ['required', 'string', 'max:255'],
        ]);

        $service->blockIp($request->ip_rule);

        return back()->with('success', 'IP blocked.');
    }

    public function throttle(Request $request, BotRuleService $service)
    {
        $request->validate([
            'ip_rule' => ['required', 'string', 'max:255'],
            'rpm' => ['nullable', 'integer', 'min:1'],
            'rph' => ['nullable', 'integer', 'min:1'],
            'rpd' => ['nullable', 'integer', 'min:1'],
        ]);

        $service->throttleIp(
            $request->ip_rule,
            $request->rpm ?: null,
            $request->rph ?: null,
            $request->rpd ?: null
        );

        return back()->with('success', 'IP throttling updated.');
    }
}
