<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;
use Kianisanaullah\TrafficSentinel\Services\RuntimeIpLookupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExploreController extends Controller
{
    protected function range(Request $request): array
    {
        $range = $request->get('range', 'today'); // today|7|30
        $days = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        $end = Carbon::now();
        $start = $days === 1 ? Carbon::today() : Carbon::now()->subDays($days)->startOfDay();

        return [$range, $days, $start, $end];
    }

    protected function host(Request $request): ?string
    {
        $host = trim((string) $request->get('host', ''));
        return $host !== '' ? strtolower($host) : null;
    }

    public function onlineHumans(Request $request)
    {
        [$range, $days] = $this->range($request);
        $minutes = (int) config('traffic-sentinel.online_minutes', 5);
        $host = $this->host($request);

        $q = TrafficSession::query()
            ->where('last_seen_at', '>=', now()->subMinutes($minutes))
            ->where('is_bot', false)
            ->orderByDesc('last_seen_at');

        if ($host) $q->where('host', $host);

        return view('traffic-sentinel::explore.online', [
            'title' => 'Online Humans',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
            'minutes' => $minutes,
            'mode' => 'humans',
        ]);
    }

    public function onlineBots(Request $request)
    {
        [$range, $days] = $this->range($request);
        $minutes = (int) config('traffic-sentinel.online_minutes', 5);
        $host = $this->host($request);

        $q = TrafficSession::query()
            ->where('last_seen_at', '>=', now()->subMinutes($minutes))
            ->where('is_bot', true)
            ->orderByDesc('last_seen_at');

        if ($host) $q->where('host', $host);

        return view('traffic-sentinel::explore.online', [
            'title' => 'Online Bots',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
            'minutes' => $minutes,
            'mode' => 'bots',
        ]);
    }

    public function uniqueHumans(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $q = TrafficSession::query()
            ->select('visitor_key', 'host')

            ->selectRaw('MAX(ip) as ip')
            ->selectRaw('MAX(user_agent) as user_agent')

            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')

            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false);

        if ($host) {
            $q->where('host', $host);
        }

        $q->groupBy('visitor_key', 'host')
            ->orderByDesc('last_seen_at');

        return view('traffic-sentinel::explore.unique_humans', [
            'title' => 'Unique Humans',
            'rows'  => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days'  => $days,
            'host'  => $host,
        ]);
    }

    public function uniqueBots(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $q = TrafficSession::query()
            ->select('visitor_key', 'bot_name', 'host')

            ->selectRaw('MAX(ip) as ip')
            ->selectRaw('MAX(user_agent) as user_agent')

            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')

            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', true);

        if ($host) {
            $q->where('host', $host);
        }

        $q->groupBy('visitor_key', 'bot_name', 'host')
            ->orderByDesc('last_seen_at');

        return view('traffic-sentinel::explore.unique_bots', [
            'title' => 'Unique Bots',
            'rows'  => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days'  => $days,
            'host'  => $host,
        ]);
    }

    public function pageviewsHumans(Request $request)
    {
        return $this->pageviews($request, false);
    }

    public function pageviewsAll(Request $request)
    {
        return $this->pageviews($request, true);
    }

    protected function pageviews(Request $request, bool $includeBots)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $q = TrafficPageview::query()
            ->with('session')
            ->whereBetween('viewed_at', [$start, $end])
            ->orderByDesc('viewed_at');

        if (! $includeBots) $q->where('is_bot', false);
        if ($host) $q->where('host', $host);

        if ($path = trim((string) $request->get('path', ''))) {
            $path = '/'.ltrim($path, '/');
            $q->where('path', $path);
        }

        return view('traffic-sentinel::explore.pageviews', [
            'title' => $includeBots ? 'Pageviews (All)' : 'Pageviews (Humans)',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
            'includeBots' => $includeBots,
            'pathFilter' => $request->get('path', ''),
        ]);
    }

    public function pages(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $q = TrafficPageview::query()
            ->select('path')
            ->selectRaw('COUNT(*) as hits')
            ->whereBetween('viewed_at', [$start, $end])
            ->where('is_bot', false);

        if ($host) $q->where('host', $host);

        $q->groupBy('path')->orderByDesc('hits');

        return view('traffic-sentinel::explore.pages', [
            'title' => 'Top Pages (Humans)',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
        ]);
    }

    public function pageviewsByPath(Request $request)
    {
        $path = '/'.ltrim((string) $request->get('path', ''), '/');
        $request->merge(['path' => $path]);
        return $this->pageviews($request, false);
    }

    public function referrers(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $q = TrafficSession::query()
            ->select('referrer')
            ->selectRaw('COUNT(*) as hits')
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '');

        if ($host) $q->where('host', $host);

        $q->groupBy('referrer')->orderByDesc('hits');

        return view('traffic-sentinel::explore.referrers', [
            'title' => 'Top Referrers (Humans)',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
        ]);
    }

    public function sessionsByReferrer(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $ref = (string) $request->get('referrer', '');

        $q = TrafficSession::query()
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->where('referrer', $ref)
            ->orderByDesc('first_seen_at');

        if ($host) $q->where('host', $host);

        return view('traffic-sentinel::explore.referrer_show', [
            'title' => 'Referrer Sessions',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
            'referrer' => Str::limit($ref, 140),
        ]);
    }
    public function sessionShow($id)
    {
        $row = TrafficSession::query()
            ->with(['pageviews' => fn($q) => $q->latest('viewed_at')->limit(50)])
            ->findOrFail($id);

        $geo = null;

        // Only if the host app has geoip() available (spatie/laravel-geoip or torann/geoip, etc.)
        try {
            if (function_exists('geoip') && $row->ip) {
                $geo = geoip($row->ip)->toArray();
            }
        } catch (\Throwable $e) {
            $geo = null;
        }

        return view('traffic-sentinel::explore.session_show', [
            'title' => 'Session Details',
            'row' => $row,
            'geo' => $geo,
        ]);
    }
    public function ipLookup(Request $request, RuntimeIpLookupService $lookup)
    {
        $ip = trim((string) $request->get('ip', ''));

        // Basic validation (v4/v6)
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return response()->json(['ok' => false, 'message' => 'Invalid IP'], 422);
        }

        $data = $lookup->lookup($ip);

        return response()->json([
            'ok' => true,
            'ip' => $ip,
            'data' => $data,
        ]);
    }
    public function uniqueIpsHumans(Request $request)
    {
        return $this->uniqueIpsIndex($request, false);
    }

    public function uniqueIpsBots(Request $request)
    {
        return $this->uniqueIpsIndex($request, true);
    }

    protected function uniqueIpsIndex(Request $request, bool $isBot)
    {
        // range
        $range = $request->get('range', 'today');
        $days  = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        [$start, $end] = $this->date_range($days);

        // filters
        $selectedHost = trim((string) $request->get('host', ''));
        if ($selectedHost === '') $selectedHost = null;

        $selectedApp = trim((string) $request->get('app', ''));
        if ($selectedApp === '') $selectedApp = null;

        $rows = DB::table('traffic_sessions as s')
            ->leftJoin('traffic_pageviews as p', 'p.traffic_session_id', '=', 's.id')
            ->selectRaw('
                s.host,
                COALESCE(s.ip_raw, s.ip) as ip,
                COUNT(DISTINCT s.id) as sessions,
                COUNT(p.id) as pageviews,
                MIN(s.first_seen_at) as first_seen_at,
                MAX(s.last_seen_at) as last_seen_at
            ')
            ->whereBetween('s.first_seen_at', [$start, $end])
            ->where('s.is_bot', $isBot)
            ->when($selectedHost, fn ($q) => $q->where('s.host', $selectedHost))
            ->when($selectedApp, fn ($q) => $q->where('s.app_key', $selectedApp))
            ->groupBy('s.host', DB::raw('COALESCE(s.ip_raw, s.ip)'))
            ->orderByDesc('last_seen_at')
            ->paginate(25)
            ->withQueryString();

        return view('traffic-sentinel::explore.unique-ips', [
            'rows'   => $rows,
            'isBot'  => $isBot,
            'range'  => $range,
            'days'   => $days,
            'title'  => $isBot ? 'Unique IPs (Bots)' : 'Unique IPs (Humans)',
        ]);
    }
    protected function date_range(int $days): array
    {
        $end   = Carbon::now();
        $start = Carbon::now()->subDays($days)->startOfDay();
        return [$start, $end];
    }
}
