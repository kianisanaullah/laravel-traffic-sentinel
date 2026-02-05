@extends('traffic-sentinel::layout')

@section('content')
<div class="ts-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold"><i class="bi bi-globe2 me-2"></i>{{ $title }}</div>
        <span class="ts-badge">{{ $referrer }}</span>
    </div>

    <div class="table-responsive">
        <table class="table ts-table table-hover mb-0">
            <thead>
            <tr>
                <th>Host</th>
                <th>Visitor Key</th>
                <th>Landing URL</th>
                <th>First Seen</th>
                <th>Last Seen</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="text-break">{{ $r->host }}</td>
                    <td class="text-break"><code>{{ $r->visitor_key }}</code></td>
                    <td class="text-break">{{ $r->landing_url }}</td>
                    <td>{{ optional($r->first_seen_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ optional($r->last_seen_at)->format('Y-m-d H:i:s') }}</td>
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
