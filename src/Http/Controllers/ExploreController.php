<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageview;
use Kianisanaullah\TrafficSentinel\Models\TrafficSession;
use Kianisanaullah\TrafficSentinel\Services\RuntimeIpLookupService;
use Illuminate\Support\Facades\Schema;

class ExploreController extends Controller
{
    /**
     * Resolve range + dates from request.
     * range: today|7|30
     */
    protected function range(Request $request): array
    {
        $range = (string) $request->get('range', 'today');
        $range = in_array($range, ['today', '7', '30'], true) ? $range : 'today';

        $days = $range === '7' ? 7 : ($range === '30' ? 30 : 1);

        $end   = Carbon::now();
        $start = $days === 1 ? Carbon::today() : Carbon::now()->subDays($days)->startOfDay();

        return [$range, $days, $start, $end];
    }

    protected function host(Request $request): ?string
    {
        $host = trim((string) $request->get('host', ''));
        return $host !== '' ? strtolower($host) : null;
    }

    protected function app(Request $request): ?string
    {
        $app = trim((string) $request->get('app', ''));
        return $app !== '' ? $app : null;
    }

    /**
     * Dropdown data for header filters
     * (kept simple, no service, no caching)
     */
    protected function filterLists(): array
    {
        $hosts = TrafficSession::query()
            ->whereNotNull('host')
            ->where('host', '!=', '')
            ->select('host')
            ->distinct()
            ->orderBy('host')
            ->pluck('host')
            ->values()
            ->all();

        $apps = TrafficSession::query()
            ->whereNotNull('app_key')
            ->where('app_key', '!=', '')
            ->select('app_key')
            ->distinct()
            ->orderBy('app_key')
            ->pluck('app_key')
            ->values()
            ->all();

        return [$hosts, $apps];
    }

    public function onlineHumans(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);

        $minutes = (int) config('traffic-sentinel.online_minutes', 5);
        $host    = $this->host($request);
        $app     = $this->app($request);

        $q = TrafficSession::query()
            ->where('last_seen_at', '>=', now()->subMinutes($minutes))
            ->where('is_bot', false)
            ->orderByDesc('last_seen_at');

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.online', [
            'title'        => 'Online Humans',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
            'minutes'      => $minutes,
            'mode'         => 'humans',
        ]);
    }

    public function onlineBots(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);

        $minutes = (int) config('traffic-sentinel.online_minutes', 5);
        $host    = $this->host($request);
        $app     = $this->app($request);

        $q = TrafficSession::query()
            ->where('last_seen_at', '>=', now()->subMinutes($minutes))
            ->where('is_bot', true)
            ->orderByDesc('last_seen_at');

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.online', [
            'title'        => 'Online Bots',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
            'minutes'      => $minutes,
            'mode'         => 'bots',
        ]);
    }

    public function uniqueHumans(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);
        $app  = $this->app($request);

        $q = TrafficSession::query()
            ->select('visitor_key', 'host')
            ->selectRaw('MAX(ip) as ip')
            ->selectRaw('MAX(ip_raw) as ip_raw')
            ->selectRaw('MAX(user_agent) as user_agent')
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false);

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

        $q->groupBy('visitor_key', 'host')
            ->orderByDesc('last_seen_at');

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.unique_humans', [
            'title'        => 'Unique Humans',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
        ]);
    }

    public function uniqueBots(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);
        $app  = $this->app($request);

        $q = TrafficSession::query()
            ->select('visitor_key', 'bot_name', 'host')
            ->selectRaw('MAX(ip) as ip')
            ->selectRaw('MAX(ip_raw) as ip_raw')
            ->selectRaw('MAX(user_agent) as user_agent')
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', true);

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

        $q->groupBy('visitor_key', 'bot_name', 'host')
            ->orderByDesc('last_seen_at');

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.unique_bots', [
            'title'        => 'Unique Bots',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
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
        $app  = $this->app($request);

        $q = TrafficPageview::query()
            ->with('session')
            ->whereBetween('viewed_at', [$start, $end])
            ->orderByDesc('viewed_at');

        if (! $includeBots) $q->where('is_bot', false);
        if ($host) $q->where('host', $host);

        // app filter: prefer direct column if exists, otherwise fallback to session relation
        if ($app) {
            if (Schema::hasColumn((new TrafficPageview)->getTable(), 'app_key')) {
                $q->where('app_key', $app);
            } else {
                $q->whereHas('session', fn ($sq) => $sq->where('app_key', $app));
            }
        }

        if ($path = trim((string) $request->get('path', ''))) {
            $path = '/' . ltrim($path, '/');
            $q->where('path', $path);
        }

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.pageviews', [
            'title'        => $includeBots ? 'Pageviews (All)' : 'Pageviews (Humans)',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
            'includeBots'  => $includeBots,
            'pathFilter'   => (string) $request->get('path', ''),
        ]);
    }

    public function pages(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);
        $app  = $this->app($request);

        $q = TrafficPageview::query()
            ->select('path')
            ->selectRaw('COUNT(*) as hits')
            ->whereBetween('viewed_at', [$start, $end])
            ->where('is_bot', false);

        if ($host) $q->where('host', $host);

        if ($app) {
            if (Schema::hasColumn((new TrafficPageview)->getTable(), 'app_key')) {
                $q->where('app_key', $app);
            } else {
                $q->whereHas('session', fn ($sq) => $sq->where('app_key', $app));
            }
        }

        $q->groupBy('path')->orderByDesc('hits');

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.pages', [
            'title'        => 'Top Pages (Humans)',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
        ]);
    }

    public function pageviewsByPath(Request $request)
    {
        $path = '/' . ltrim((string) $request->get('path', ''), '/');
        $request->merge(['path' => $path]);

        return $this->pageviews($request, false);
    }

    public function referrers(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);
        $app  = $this->app($request);

        $refType = $request->get('ref_type', 'outside');

        $rawRefHost = "LOWER(
        CASE
            WHEN referrer LIKE '%//%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '//', -1), '/', 1)
            ELSE SUBSTRING_INDEX(referrer, '/', 1)
        END
    )";

        $normRefHost = "CASE
        WHEN LEFT(($rawRefHost), 4) = 'www.' THEN SUBSTRING(($rawRefHost), 5)
        ELSE ($rawRefHost)
    END";

        $rawSessHost = "LOWER(COALESCE(host,''))";
        $normSessHost = "CASE
        WHEN LEFT(($rawSessHost), 4) = 'www.' THEN SUBSTRING(($rawSessHost), 5)
        ELSE ($rawSessHost)
    END";

        $baseRef  = "SUBSTRING_INDEX(($normRefHost), '.', -2)";
        $baseSess = "SUBSTRING_INDEX(($normSessHost), '.', -2)";

        $q = TrafficSession::query()
            ->select('referrer')
            ->selectRaw('COUNT(*) as hits')
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '');

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

        if ($refType === 'internal') {
            $q->whereRaw("$normRefHost = $normSessHost");
        } elseif ($refType === 'domain') {
            $q->whereRaw("$baseRef = $baseSess")
                ->whereRaw("$normRefHost != $normSessHost");
        } elseif ($refType === 'outside') {
            $q->whereRaw("$baseRef != $baseSess");
        }

        $q->groupBy('referrer')->orderByDesc('hits');

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.referrers', [
            'title'        => 'Top Referrers (Humans)',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
            'refType'      => $refType,
        ]);
    }

    public function sessionsByReferrer(Request $request)
    {
        [$range, $days, $start, $end] = $this->range($request);
        $host = $this->host($request);
        $app  = $this->app($request);

        $ref = (string) $request->get('referrer', '');

        $q = TrafficSession::query()
            ->whereBetween('first_seen_at', [$start, $end])
            ->where('is_bot', false)
            ->where('referrer', $ref)
            ->orderByDesc('first_seen_at');

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.referrer_show', [
            'title'        => 'Referrer Sessions',
            'rows'         => $q->paginate(30)->withQueryString(),
            'range'        => $range,
            'days'         => $days,
            'selectedHost' => $host,
            'selectedApp'  => $app,
            'hosts'        => $hosts,
            'apps'         => $apps,
            'referrer'     => Str::limit($ref, 140),
        ]);
    }

    public function sessionShow($id)
    {
        $row = TrafficSession::query()
            ->with(['pageviews' => fn ($q) => $q->latest('viewed_at')->limit(50)])
            ->findOrFail($id);

        $geo = null;

        try {
            if (function_exists('geoip') && $row->ip) {
                $geo = geoip($row->ip)->toArray();
            }
        } catch (\Throwable $e) {
            $geo = null;
        }

        // keep filters in header
        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.session_show', [
            'title' => 'Session Details',
            'row'   => $row,
            'geo'   => $geo,
            'hosts' => $hosts,
            'apps'  => $apps,
            'selectedHost' => null,
            'selectedApp'  => null,
            'range' => 'today',
            'days'  => 1,
        ]);
    }

    public function ipLookup(Request $request, RuntimeIpLookupService $lookup)
    {
        $ip = trim((string) $request->get('ip', ''));

        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return response()->json(['ok' => false, 'message' => 'Invalid IP'], 422);
        }

        $data = $lookup->lookup($ip);

        return response()->json([
            'ok'   => true,
            'ip'   => $ip,
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
        [$range, $days, $start, $end] = $this->range($request);

        $selectedHost = $this->host($request);
        $selectedApp  = $this->app($request);

        // use configured connection (future-proof)
        $conn = config('traffic-sentinel.database.connection', config('database.default', 'mysql'));

        $rows = DB::connection($conn)
            ->table('traffic_sessions as s')
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

        [$hosts, $apps] = $this->filterLists();

        return view('traffic-sentinel::explore.unique-ips', [
            'rows'         => $rows,
            'isBot'        => $isBot,
            'range'        => $range,
            'days'         => $days,
            'title'        => $isBot ? 'Unique IPs (Bots)' : 'Unique IPs (Humans)',
            'hosts'        => $hosts,
            'apps'         => $apps,
            'selectedHost' => $selectedHost,
            'selectedApp'  => $selectedApp,
        ]);
    }
}
