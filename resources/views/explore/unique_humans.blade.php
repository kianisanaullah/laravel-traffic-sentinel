@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold"><i class="bi bi-fingerprint me-2"></i>{{ $title }}</div>
            <span class="ts-badge">Range: {{ $range === 'today' ? 'Today' : "Last $days days" }}</span>
        </div>

        <div class="table-responsive">
            <table id="tsTable" class="table ts-table table-hover mb-0">
                <thead>
                <tr>
                    <th>Host</th>
                    <th>IP</th>
                    <th>User Agent</th>
                    <th>Visitor Key</th>
                    <th class="text-end">Sessions</th>
                    <th>First Seen</th>
                    <th>Last Seen</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                        <?php
                        $p = \Kianisanaullah\TrafficSentinel\Support\UserAgentParser::parse($r->user_agent);
                        ?>
                    <tr>
                        <td class="text-break">{{ $r->host }}</td>

                        <td class="text-break">
                            @include('traffic-sentinel::partials.ip-cell', ['ip' => ($r->ip_raw ?? $r->ip)])
                        </td>
                        <td class="text-break">
                            <div class="fw-semibold">{{ \Kianisanaullah\TrafficSentinel\Support\UserAgentParser::label($p) }}</div>
                            <div class="text-muted small" title="{{ $p['ua'] }}">
                                {{ \Illuminate\Support\Str::limit($p['ua'], 80) }}
                            </div>
                        </td>

                        <td class="text-break">
                            <code>{{ $r->visitor_key }}</code>
                        </td>

                        <td class="text-end fw-semibold">
                            {{ (int) $r->sessions }}
                        </td>

                        <td>{{ \Carbon\Carbon::parse($r->first_seen_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->last_seen_at)->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center ts-empty">No records</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $rows->withQueryString()->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#tsTable', {
                order: [[5, 'desc']],
                paging: false,
                info: false,
                lengthChange: false,
            });
        });
    </script>
@endpush
