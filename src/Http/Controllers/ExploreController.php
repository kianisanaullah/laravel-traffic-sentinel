<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;

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
            ->selectRaw('MAX(last_seen_at) as last_seen_at')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('COUNT(*) as sessions')
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false);

        if ($host) $q->where('host', $host);

        $q->groupBy('visitor_key', 'host')
          ->orderByDesc('last_seen_at');

        return view('traffic-sentinel::explore.unique_humans', [
            'title' => 'Unique Humans',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
        ]);
    }

    public function uniqueBots(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);

        $q = TrafficSession::query()
            ->select('visitor_key', 'bot_name', 'host')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('COUNT(*) as sessions')
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', true);

        if ($host) $q->where('host', $host);

        $q->groupBy('visitor_key', 'bot_name', 'host')
          ->orderByDesc('last_seen_at');

        return view('traffic-sentinel::explore.unique_bots', [
            'title' => 'Unique Bots',
            'rows' => $q->paginate(30)->withQueryString(),
            'range' => $range,
            'days' => $days,
            'host' => $host,
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
}
