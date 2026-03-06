<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewHuman;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewBot;

class IpLogsController extends Controller
{
    public function humans(Request $request)
    {
        return $this->renderIndex($request, 'humans');
    }

    public function bots(Request $request)
    {
        return $this->renderIndex($request, 'bots');
    }

    public function dataHumans(Request $request)
    {
        return $this->data($request, 'humans');
    }

    public function dataBots(Request $request)
    {
        return $this->data($request, 'bots');
    }

    private function renderIndex(Request $request, string $mode)
    {
        $hour = (int) $request->get('hour', 1);
        if (!in_array($hour, [1, 6, 12, 24], true)) $hour = 1;

        $start = now()->subHours($hour);

        // optional filters (if you want to keep dashboard filters sticky)
        $host = trim((string) $request->get('host', '')) ?: null;
        $app  = trim((string) $request->get('app', '')) ?: null;

        $hour1  = $this->uniqueIpCount(now()->subHour(),  $mode, $host, $app);
        $hour24 = $this->uniqueIpCount(now()->subHours(24), $mode, $host, $app);

        $ipCountDay = $this->ipCountsSince($start, $mode, $host, $app)
            ->orderByDesc('count')
            ->limit(2000)
            ->get();

        return view('traffic-sentinel::ip-logs.index', compact(
            'hour',
            'hour1',
            'hour24',
            'ipCountDay',
            'mode',
            'host',
            'app'
        ));
    }

    public function focus(Request $request, string $ip)
    {
        $mode = strtolower((string) $request->get('mode', 'humans'));
        if (!in_array($mode, ['humans','bots'], true)) $mode = 'humans';

        return redirect()->route(
            $mode === 'bots' ? 'traffic-sentinel.ip-logs.bots' : 'traffic-sentinel.ip-logs.humans',
            array_merge($request->except('page'), ['hour' => 24, 'focus' => $ip])
        );
    }

    /**
     * DataTables endpoint: recent pageviews for a given IP (per mode).
     */
    private function data(Request $request, string $mode)
    {
        $ip = trim((string) $request->get('ip', ''));
        if ($ip === '') {
            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $draw   = (int) $request->get('draw', 1);
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 200);
        $length = max(10, min($length, 500));

        $search = trim((string) data_get($request->all(), 'search.value', ''));

        $host = trim((string) $request->get('host', '')) ?: null;
        $app  = trim((string) $request->get('app', '')) ?: null;

        $base = $this->ipDetailsQuery($ip, $mode, $host, $app);

        $recordsTotal = DB::query()->fromSub($base, 'q')->count();

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('path', 'like', "%{$search}%")
                    ->orWhere('host', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = DB::query()->fromSub($base, 'q')->count();

        $orderColIndex = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDir      = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderMap = [
            0 => 'path',
            1 => 'viewed_at',
            2 => 'host',
            3 => 'user_agent',
        ];
        $orderCol = $orderMap[$orderColIndex] ?? 'viewed_at';

        $rows = DB::query()
            ->fromSub($base, 'q')
            ->orderBy($orderCol, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($r) {
            $path = (string) ($r->path ?? '');
            $host = (string) ($r->host ?? '');
            $ua   = (string) ($r->user_agent ?? '');
            $dt   = (string) ($r->viewed_at ?? '');

            $domainText = $host !== '' ? $host : '-';

            $link = e($path);
            if ($host !== '' && $path !== '') {
                $url = 'https://' . $host . $path;
                $link = '<a href="' . e($url) . '" target="_blank" rel="noopener">' . e($path) . '</a>';
            }

            return [
                'link'        => $link,
                'created_at'  => e($dt),
                'domain_text' => e($domainText),
                'user_agent'  => e($ua),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function uniqueIpCount($since, string $mode, ?string $host, ?string $app): int
    {
        $q = $this->ipCountsSince($since, $mode, $host, $app);
        return (int) DB::query()->fromSub($q, 'x')->count();
    }

    /**
     * Returns grouped IP list query for a mode since time.
     * output: ip, ip_raw, count
     */
    private function ipCountsSince($since, string $mode, ?string $host, ?string $app)
    {
        $q = $mode === 'bots'
            ? TrafficPageviewBot::query()
            : TrafficPageviewHuman::query();

        return $q
            ->where('viewed_at', '>=', $since)
            ->when($host, fn($qq) => $qq->where('host', $host))
            ->when($app,  fn($qq) => $qq->where('app_key', $app))
            ->selectRaw("COALESCE(NULLIF(ip_raw,''), ip) as ip")
            ->selectRaw("MAX(ip_raw) as ip_raw")
            ->selectRaw("COUNT(*) as count")
            ->groupBy(DB::raw("COALESCE(NULLIF(ip_raw,''), ip)"));

    }

    /**
     * details query for datatable (per mode)
     */
    private function ipDetailsQuery(string $ip, string $mode, ?string $host, ?string $app)
    {
        $q = $mode === 'bots'
            ? TrafficPageviewBot::query()
            : TrafficPageviewHuman::query();

        return $q
            ->where(function ($qq) use ($ip) {
                $qq->where('ip', $ip)->orWhere('ip_raw', $ip);
            })
            ->when($host, fn($qq) => $qq->where('host', $host))
            ->when($app,  fn($qq) => $qq->where('app_key', $app))
            ->select(['viewed_at','host','path','user_agent']);
    }
}
