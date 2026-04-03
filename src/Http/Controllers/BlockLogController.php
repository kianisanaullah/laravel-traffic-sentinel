<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewBot;
use Kianisanaullah\TrafficSentinel\Models\TrafficPageviewHuman;
use Kianisanaullah\TrafficSentinel\Services\FilterService;

class BlockLogController extends Controller
{
    protected $filters;

    public function __construct(FilterService $filters)
    {
        $this->filters = $filters;
    }
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $reason = $request->get('reason');

        $logs = DB::table('traffic_blocked_attempts')
            ->when($q, fn($qBuilder) =>
            $qBuilder->where('ip', 'like', "%{$q}%")
            )
            ->when($reason, fn($qBuilder) =>
            $qBuilder->where('reason', $reason)
            )
            ->orderByDesc('last_hit_at')
            ->paginate(50)
            ->withQueryString();

        return view('traffic-sentinel::block-logs.index', compact('logs'));
    }
}
