@extends('traffic-sentinel::layout')

@section('content')
<div class="ts-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold"><i class="bi bi-robot me-2"></i>{{ $title }}</div>
        <span class="ts-badge">Range: {{ $range === 'today' ? 'Today' : "Last $days days" }}</span>
    </div>

    <div class="table-responsive">
        <table class="table ts-table table-hover mb-0">
            <thead>
            <tr>
                <th>Host</th>
                <th>Bot Name</th>
                <th>Visitor Key</th>
                <th class="text-end">Sessions</th>
                <th>Last Seen</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="text-break">{{ $r->host }}</td>
                    <td class="text-break">
                        <span class="ts-badge"><i class="bi bi-cpu me-1"></i>{{ $r->bot_name ?: 'unknown' }}</span>
                    </td>
                    <td class="text-break"><code>{{ $r->visitor_key }}</code></td>
                    <td class="text-end fw-semibold">{{ (int) $r->sessions }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->last_seen_at)->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center ts-empty">No records</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->withQueryString()->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
