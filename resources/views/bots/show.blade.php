@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card">

        {{-- HEADER --}}
        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div class="fw-semibold">
                    <i class="bi bi-robot me-2"></i>
                    {{ $bot ?? 'Unknown Bot' }}
                </div>

                <a href="{{ route('traffic-sentinel.bots.index') }}" class="btn btn-sm btn-outline-secondary">
                    ← Back
                </a>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <div class="row g-3">

                <div class="col-md-3">
                    <div class="ts-pill">Sessions: {{ number_format($summary->sessions ?? 0) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="ts-pill">IPs: {{ number_format($summary->ips ?? 0) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="ts-pill">
                        Last Seen:
                        {{ $summary->last_seen ? \Carbon\Carbon::parse($summary->last_seen)->format('Y-m-d H:i') : '—' }}
                    </div>
                </div>

                <div class="col-md-3">
                    @if(!$rule)
                        <span class="ts-badge">Unconfigured</span>
                    @elseif($rule->action === 'block')
                        <span class="ts-badge bg-danger">Blocked</span>
                    @elseif($rule->action === 'throttle')
                        <span class="ts-badge bg-warning text-dark">Throttled</span>
                    @else
                        <span class="ts-badge bg-success">Monitoring</span>
                    @endif
                </div>

            </div>
        </div>

        {{-- IP TABLE --}}
        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th>IP</th>
                    <th>Sessions</th>
                    <th>Last Seen</th>
                    <th>Visited Pages</th>
                </tr>
                </thead>

                <tbody>
                @foreach($ips as $ip)
                    <tr>

                        <td>
                            <span class="ts-badge">{{ $ip->ip }}</span>
                        </td>

                        <td>
                            <span class="ts-pill">{{ number_format($ip->sessions) }}</span>
                        </td>

                        <td>
                        <span class="ts-badge">
                            {{ \Carbon\Carbon::parse($ip->last_seen)->format('Y-m-d H:i') }}
                        </span>
                        </td>

                        <td>
                            <div class="d-flex flex-column gap-1">

                                @foreach(($pages[$ip->ip] ?? []) as $page)
                                    <span class="ts-pill" style="font-size:11px">
                                    {{ Str::limit($page->full_url ?? $page->path, 60) }}
                                    ({{ $page->visits }})
                                </span>
                                @endforeach

                            </div>
                        </td>

                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="p-3">
            {{ $ips->links() }}
        </div>

    </div>
@endsection
