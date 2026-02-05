@extends('traffic-sentinel::layout')

@section('content')
<div class="ts-card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold"><i class="bi bi-eye me-2"></i>{{ $title }}</div>
        <span class="ts-badge">Range: {{ $range === 'today' ? 'Today' : "Last $days days" }}</span>
    </div>

    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="range" value="{{ request('range','today') }}">
        <div class="col-md-4">
            <label class="form-label small mb-1">Host</label>
            <input type="text" name="host" value="{{ request('host') }}" class="form-control form-control-sm" placeholder="example.com">
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1">Path filter (optional)</label>
            <input type="text" name="path" value="{{ request('path') }}" class="form-control form-control-sm" placeholder="/admin/traffic-sentinel">
        </div>
        <div class="col-md-2">
            <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>
</div>

<div class="ts-card p-0">
    <div class="table-responsive">
        <table class="table ts-table table-hover mb-0">
            <thead>
            <tr>
                <th>Time</th>
                <th>Host</th>
                <th>Method</th>
                <th>Path</th>
                <th>Status</th>
                <th>Bot</th>
                <th class="text-end">Ms</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ optional($r->viewed_at)->format('Y-m-d H:i:s') }}</td>
                    <td class="text-break">{{ $r->host }}</td>
                    <td><span class="ts-badge">{{ $r->method }}</span></td>
                    <td class="text-break">{{ $r->path }}</td>
                    <td>{{ $r->status_code }}</td>
                    <td>
                        @if($r->is_bot)
                            <span class="ts-badge"><i class="bi bi-robot me-1"></i>{{ $r->bot_name ?: 'bot' }}</span>
                        @else
                            <span class="ts-badge"><i class="bi bi-person me-1"></i>human</span>
                        @endif
                    </td>
                    <td class="text-end">{{ (int) $r->duration_ms }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center ts-empty">No records</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">{{ $rows->withQueryString()->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
