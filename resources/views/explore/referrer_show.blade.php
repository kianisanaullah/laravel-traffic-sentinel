@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card p-3">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">
                <i class="bi bi-globe2 me-2"></i>{{ $title }}
            </div>
            <span class="ts-badge">{{ $referrer }}</span>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle" id="tsTable">
                <thead>
                <tr>
                    <th style="min-width:140px">Host</th>
                    <th style="min-width:150px">IP</th>
                    <th style="min-width:260px">User Agent</th>
                    <th style="min-width:160px">Visitor Key</th>
                    <th>Landing URL</th>
                    <th style="width:160px">First Seen</th>
                    <th style="width:160px">Last Seen</th>
                </tr>
                </thead>

                <tbody>
                @forelse($rows as $r)
                    @php
                        $p = \Kianisanaullah\TrafficSentinel\Support\UserAgentParser::parse($r->user_agent);
                    @endphp
                    <tr>

                        {{-- Host --}}
                        <td class="text-break">
                        <span class="ts-badge">
                            <i class="bi bi-globe2 me-1"></i>{{ $r->host ?? '—' }}
                        </span>
                        </td>

                        <td class="text-break">
                            @include('traffic-sentinel::partials.ip-cell', ['ip' => ($r->ip_raw ?? $r->ip)])
                        </td>

                        {{-- User Agent --}}
                        <td class="text-break">
                            <div class="fw-semibold">
                                {{ \Kianisanaullah\TrafficSentinel\Support\UserAgentParser::label($p) }}
                            </div>
                            <div class="text-muted small" title="{{ $p['ua'] }}">
                                {{ \Illuminate\Support\Str::limit($p['ua'], 90) }}
                            </div>
                        </td>

                        {{-- Visitor Key --}}
                        <td class="text-break">
                            <code class="small">{{ $r->visitor_key }}</code>
                        </td>

                        {{-- Landing URL --}}
                        <td class="text-break">
                            {{ $r->landing_url ?? '—' }}
                        </td>

                        {{-- First Seen --}}
                        <td class="text-nowrap">
                            {{ optional($r->first_seen_at)->format('Y-m-d H:i:s') }}
                        </td>

                        {{-- Last Seen --}}
                        <td class="text-nowrap">
                            {{ optional($r->last_seen_at)->format('Y-m-d H:i:s') }}
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center ts-empty">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No records
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection

{{-- DataTables --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#tsTable', {
                order: [[6, 'desc']], // Last Seen
                paging: false,
                info: false,
                lengthChange: false,
                searching: true,
                responsive: true,
                columnDefs: [
                    { targets: [1,2,4], orderable: false } // IP, UA, URL
                ]
            });
        });
    </script>
@endpush
