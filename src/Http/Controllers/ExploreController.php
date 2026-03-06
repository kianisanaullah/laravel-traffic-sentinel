<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kianisanaullah\TrafficSentinel\Services\RuntimeIpLookupService;

use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewHuman;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewBot;
use Kianisanaullah\TrafficSentinel\Models\TrafficSessionHuman;
use Kianisanaullah\TrafficSentinel\Models\TrafficSessionBot;

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
     * Return correct model/table based on bot/human.
     */
    protected function sessModel(bool $isBot): string
    {
        return $isBot ? TrafficSessionBot::class : TrafficSessionHuman::class;
    }

    protected function pvModel(bool $includeBots): array
    {
        // returns [humansModel, botsModel|null]
        return $includeBots
            ? [TrafficPageviewHuman::class, TrafficPageviewBot::class]
            : [TrafficPageviewHuman::class, null];
    }

    /**
     * Dropdown data for header filters (fast: read sessions humans+bots union)
     */
    protected function filterLists(): array
    {
        // Hosts
        $hostsHum = TrafficSessionHuman::query()
            ->whereNotNull('host')->where('host', '!=', '')
            ->select('host')->distinct();

        $hostsBot = TrafficSessionBot::query()
            ->whereNotNull('host')->where('host', '!=', '')
            ->select('host')->distinct();

        $hosts = $hostsHum->union($hostsBot)
            ->orderBy('host')
            ->pluck('host')
            ->values()
            ->all();

        // Apps
        $appsHum = TrafficSessionHuman::query()
            ->whereNotNull('app_key')->where('app_key', '!=', '')
            ->select('app_key')->distinct();

        $appsBot = TrafficSessionBot::query()
            ->whereNotNull('app_key')->where('app_key', '!=', '')
            ->select('app_key')->distinct();

        $apps = $appsHum->union($appsBot)
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

        $q = TrafficSessionHuman::query()
            ->where('last_seen_at', '>=', now()->subMinutes($minutes))
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

        $q = TrafficSessionBot::query()
            ->where('last_seen_at', '>=', now()->subMinutes($minutes))
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

        $q = TrafficSessionHuman::query()
            ->select('visitor_key', 'host')
            ->selectRaw('MAX(ip) as ip')
            ->selectRaw('MAX(ip_raw) as ip_raw')
            ->selectRaw('MAX(user_agent) as user_agent')
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')
            ->whereBetween('first_seen_at', [$start, $end]);

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

        $q = TrafficSessionBot::query()
            ->select('visitor_key', 'bot_name', 'host')
            ->selectRaw('MAX(ip) as ip')
            ->selectRaw('MAX(ip_raw) as ip_raw')
            ->selectRaw('MAX(user_agent) as user_agent')
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('MIN(first_seen_at) as first_seen_at')
            ->selectRaw('MAX(last_seen_at) as last_seen_at')
            ->whereBetween('first_seen_at', [$start, $end]);

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

        [$humansModel, $botsModel] = $this->pvModel($includeBots);

        $hq = $humansModel::query()
            ->whereBetween('viewed_at', [$start, $end]);

        if ($host) $hq->where('host', $host);
        if ($app)  $hq->where('app_key', $app);

        if ($path = trim((string) $request->get('path', ''))) {
            $path = '/' . ltrim($path, '/');
            $hq->where('path', $path);
        }

        if (! $includeBots) {
            $q = $hq->orderByDesc('viewed_at');
        } else {
            $bq = $botsModel::query()
                ->whereBetween('viewed_at', [$start, $end]);

            if ($host) $bq->where('host', $host);
            if ($app)  $bq->where('app_key', $app);

            if ($path = trim((string) $request->get('path', ''))) {
                $path = '/' . ltrim($path, '/');
                $bq->where('path', $path);
            }

            // UNION humans + bots into one list
            $q = $hq->select('*')->unionAll($bq->select('*'))->orderByDesc('viewed_at');
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

        $q = TrafficPageviewHuman::query()
            ->select('path')
            ->selectRaw('COUNT(*) as hits')
            ->whereBetween('viewed_at', [$start, $end]);

        if ($host) $q->where('host', $host);
        if ($app)  $q->where('app_key', $app);

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

        $q = TrafficSessionHuman::query()
            ->select('referrer')
            ->selectRaw('COUNT(*) as hits')
            ->whereBetween('first_seen_at', [$start, $end])
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

        $q = TrafficSessionHuman::query()
            ->whereBetween('first_seen_at', [$start, $end])
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
        // humans + bots lookup (humans first)
        $row = TrafficSessionHuman::find($id);
        $isBot = false;

        if (! $row) {
            $row = TrafficSessionBot::findOrFail($id);
            $isBot = true;
        }

        // Load last 50 pageviews for this session from proper table
        if ($isBot) {
            $pageviews = TrafficPageviewBot::query()
                ->where('traffic_session_id', $row->id)
                ->latest('viewed_at')
                ->limit(50)
                ->get();
        } else {
            $pageviews = TrafficPageviewHuman::query()
                ->where('traffic_session_id', $row->id)
                ->latest('viewed_at')
                ->limit(50)
                ->get();
        }

        // attach dynamic relation-like property used in blade (if any)
        $row->setRelation('pageviews', $pageviews);

        $geo = null;

        try {
            if (function_exists('geoip') && $row->ip) {
                $geo = geoip($row->ip)->toArray();
            }
        } catch (\Throwable $e) {
            $geo = null;
        }

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

        $conn = config('traffic-sentinel.database.connection', config('database.default', 'mysql'));

        $sessTable = $isBot ? 'traffic_sessions_bots' : 'traffic_sessions_humans';
        $pvTable   = $isBot ? 'traffic_pageviews_bots' : 'traffic_pageviews_humans';

        $rows = DB::connection($conn)
            ->table($sessTable . ' as s')
            ->leftJoin($pvTable . ' as p', 'p.traffic_session_id', '=', 's.id')
            ->selectRaw('
                s.host,
                COALESCE(s.ip_raw, s.ip) as ip,
                COUNT(DISTINCT s.id) as sessions,
                COUNT(p.id) as pageviews,
                MIN(s.first_seen_at) as first_seen_at,
                MAX(s.last_seen_at) as last_seen_at
            ')
            ->whereBetween('s.first_seen_at', [$start, $end])
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
