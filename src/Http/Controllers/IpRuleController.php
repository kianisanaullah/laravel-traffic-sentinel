<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotRuleService;
use Kianisanaullah\TrafficSentinel\Services\RuntimeIpLookupService;

class IpRuleController extends Controller
{
    public function index(Request $request)
    {
        $limitDate = now()->subDays(15);

        $ipFilter = trim((string) $request->get('ip', ''));
        $typeFilter = trim((string) $request->get('type', ''));
        $statusFilter = trim((string) $request->get('status', ''));

        // Humans
        $humanIps = DB::table('traffic_sessions_humans')
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($ipFilter !== '', function ($q) use ($ipFilter) {
                $q->where('ip', 'like', "%{$ipFilter}%");
            })
            ->selectRaw("
            ip,
            COUNT(*) as sessions,
            MAX(last_seen_at) as last_seen,
            1 as human_count,
            0 as bot_count
        ")
            ->groupBy('ip');

        // Bots
        $botIps = DB::table('traffic_sessions_bots')
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->where('last_seen_at', '>=', $limitDate)
            ->when($ipFilter !== '', function ($q) use ($ipFilter) {
                $q->where('ip', 'like', "%{$ipFilter}%");
            })
            ->selectRaw("
            ip,
            COUNT(*) as sessions,
            MAX(last_seen_at) as last_seen,
            0 as human_count,
            1 as bot_count
        ")
            ->groupBy('ip');

        // Merge and aggregate
        $ips = DB::query()
            ->fromSub($humanIps->unionAll($botIps), 'x')
            ->selectRaw("
            ip,
            SUM(sessions) as sessions,
            MAX(last_seen) as last_seen,
            CASE
                WHEN SUM(human_count) > 0 AND SUM(bot_count) > 0 THEN 'mixed'
                WHEN SUM(bot_count) > 0 THEN 'bot'
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

        // Get IPs from current page
        $pageIps = $ips->pluck('ip');

        // Fetch rules for these IPs only
        $rules = DB::table('traffic_bot_rules')
            ->whereIn('ip', $pageIps)
            ->select('ip', 'action', 'limit_per_minute', 'limit_per_hour', 'limit_per_day')
            ->get()
            ->keyBy('ip');

        // Status filter (after rules loaded)
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

    public function show($ip, RuntimeIpLookupService $lookup)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            abort(404);
        }

        $limitDate = now()->subDays(15);

        $humanViews = DB::table('traffic_pageviews_humans')
            ->where('ip', $ip)
            ->where('viewed_at', '>=', $limitDate)
            ->selectRaw("
            'human' as type,
            method,
            path,
            full_url,
            route_name,
            status_code,
            duration_ms,
            referrer,
            session_id,
            visitor_key,
            user_id,
            NULL as bot_name,
            viewed_at
        ");

        $botViews = DB::table('traffic_pageviews_bots')
            ->where('ip', $ip)
            ->where('viewed_at', '>=', $limitDate)
            ->selectRaw("
            'bot' as type,
            method,
            path,
            full_url,
            route_name,
            status_code,
            duration_ms,
            referrer,
            session_id,
            visitor_key,
            NULL as user_id,
            bot_name,
            viewed_at
        ");

        $visits = DB::query()
            ->fromSub($humanViews->unionAll($botViews), 'v')
            ->orderByDesc('viewed_at')
            ->paginate(50);

        $rule = DB::table('traffic_bot_rules')
            ->where('ip', $ip)
            ->first();

        $geo = $lookup->lookup($ip);

        return view('traffic-sentinel::ips.focus', compact(
            'ip',
            'visits',
            'rule',
            'geo'
        ));
    }
    public function whitelist_index()
    {
        $ips = DB::table('traffic_whitelist_ips')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('traffic-sentinel::ips.whitelist', compact('ips'));
    }
    public function whitelist_store(Request $request)
    {
        $request->validate([
            'ip' => ['required', 'string', function ($attr, $value, $fail) {

                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return;
                }

                if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $value)) {

                    [$ip, $mask] = explode('/', $value);

                    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $fail('Invalid subnet IP');
                    }

                    if ($mask < 0 || $mask > 32) {
                        return $fail('Invalid subnet mask');
                    }

                    return;
                }

                $fail('Invalid IP or subnet format');
            }],

            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expires_at' => 'nullable|date'
        ]);

        $type = str_contains($request->ip, '/') ? 'subnet' : 'ip';

        DB::table('traffic_whitelist_ips')->insert([
            'ip' => $request->ip,
            'type' => $type,
            'name' => $request->name,
            'description' => $request->description,
            'expires_at' => $request->expires_at,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Cache::forget('ts_whitelist_all');

        return back()->with('success', $type === 'subnet'
            ? 'Subnet added to whitelist'
            : 'IP added to whitelist');
    }
    public function whitelist_destroy($id)
    {
        $row = DB::table('traffic_whitelist_ips')->where('id',$id)->first();

        if($row){
            Cache::forget('ts_whitelist_'.$row->ip);
        }

        DB::table('traffic_whitelist_ips')->where('id',$id)->delete();

        return back()->with('success','Whitelist entry removed');
    }
}
