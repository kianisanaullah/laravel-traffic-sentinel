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

                <div class="d-flex gap-2 align-items-center flex-wrap">

                <span class="ts-badge">
                    <i class="bi bi-calendar me-1"></i>
                    Last {{ $days ?? 15 }} Days
                </span>

                    @if(!empty($host))
                        <span class="ts-badge">
                        <i class="bi bi-globe me-1"></i>
                        {{ $host }}
                    </span>
                    @endif

                    <a href="{{ route('traffic-sentinel.bots.index', request()->query()) }}"
                       class="btn btn-sm btn-outline-secondary">
                        ← Back
                    </a>

                </div>
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

        {{-- HELPERS --}}
        @php
            function subnet($ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $parts = explode('.', $ip);
                    return "{$parts[0]}.{$parts[1]}.{$parts[2]}.0/24";
                }
                return 'IPv6';
            }

            function subnetColor($subnet) {
                $hash = md5($subnet);

                $r = hexdec(substr($hash, 0, 2));
                $g = hexdec(substr($hash, 2, 2));
                $b = hexdec(substr($hash, 4, 2));

                // keep colors darker for readability
                $r = $r % 200;
                $g = $g % 200;
                $b = $b % 200;

                return "rgb($r,$g,$b)";
            }
        @endphp

        {{-- IP TABLE --}}
        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th>IP</th>
                    <th>Sessions</th>
                    <th>Last Seen</th>
                    <th style="width:120px">Details</th>
                </tr>
                </thead>

                <tbody>
                @foreach($ips as $ip)
                    @php
                        $collapseId = 'ip-' . md5($ip->ip);
                        $subnet = subnet($ip->ip);
                        $color = subnetColor($subnet);
                    @endphp

                    {{-- MAIN ROW --}}
                    <tr>

                        <td>
                            <div class="d-flex flex-column">

                            <span class="ts-badge"
                                  style="background: {{ $color }}; color:#fff;">
                                {{ $ip->ip }}
                            </span>

                                <small style="opacity:.6; font-size:10px">
                                    {{ $subnet }}
                                </small>

                            </div>
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
                            <button
                                    class="btn btn-sm btn-outline-light"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $collapseId }}">
                                <i class="bi bi-chevron-down me-1"></i>
                                View
                            </button>
                        </td>

                    </tr>

                    {{-- EXPANDABLE --}}
                    <tr class="collapse-row">
                        <td colspan="4" class="p-0 border-0">

                            <div class="collapse" id="{{ $collapseId }}">

                                <div class="p-3" style="background: rgba(255,255,255,.02)">

                                    <div class="fw-semibold mb-2">
                                        <i class="bi bi-globe me-1"></i>Visited Pages
                                    </div>

                                    @php
                                        $ipPages = $pages[$ip->ip] ?? collect();
                                    @endphp

                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($ipPages->take(10) as $page)
                                            <span class="ts-pill small">
                                            {{ Str::limit($page->full_url ?? $page->path, 60) }}
                                            ({{ $page->visits }})
                                        </span>
                                        @endforeach
                                    </div>

                                    @if($ipPages->count() > 10)
                                        <div class="mt-2">
                                            <button
                                                    class="btn btn-sm btn-link text-info p-0"
                                                    onclick="this.nextElementSibling.classList.toggle('d-none')">
                                                Show More
                                            </button>

                                            <div class="d-none mt-2 d-flex flex-wrap gap-2">
                                                @foreach($ipPages->slice(10) as $page)
                                                    <span class="ts-pill small">
                                                    {{ Str::limit($page->full_url ?? $page->path, 60) }}
                                                    ({{ $page->visits }})
                                                </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                </div>

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

@push('styles')
    <style>
        .ts-pill.small {
            font-size: 11px;
            opacity: 0.85;
        }

        .collapse-row td {
            border-top: none !important;
        }

        .table tr:hover {
            background: rgba(255,255,255,0.03);
        }

        .btn-outline-light {
            border-color: rgba(255,255,255,.2);
        }
    </style>
@endpush
