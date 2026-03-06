@extends('traffic-sentinel::layout')

@section('title', 'IP Logs')

@section('content')
    @php
        $mode = $mode ?? 'humans';

        $routeBase = $mode === 'bots'
            ? 'traffic-sentinel.ip-logs.bots'
            : 'traffic-sentinel.ip-logs.humans';

        $routeData = $mode === 'bots'
            ? 'traffic-sentinel.ip-logs.bots.data'
            : 'traffic-sentinel.ip-logs.humans.data';

        // keep filters if you later pass host/app/range etc
        $sticky = request()->except(['hour','focus','page']);
    @endphp

    <style>
        /* --- match dashboard theme --- */
        .ts-wrap {
            max-width: 100%;
        }

        .ts-card {
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06);
            box-shadow: 0 18px 40px rgba(0,0,0,.25);
            backdrop-filter: blur(10px);
        }

        [data-bs-theme="light"] .ts-card {
            background: #fff;
            border-color: rgba(15,23,42,.08);
            box-shadow: 0 16px 35px rgba(15,23,42,.08);
        }

        .ts-title {
            font-weight: 900;
            letter-spacing: .2px;
        }

        .ts-subtitle {
            opacity: .75;
        }

        .ts-pill {
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .9rem;
            white-space: nowrap;
        }

        [data-bs-theme="light"] .ts-pill {
            border-color: rgba(15,23,42,.10);
            background: rgba(15,23,42,.03);
            color: rgba(15,23,42,.75);
        }

        .ts-btn {
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(99,102,241,.25);
            color: #fff;
            padding: .45rem .85rem;
            font-weight: 600;
            transition: transform .12s ease, background .12s ease, border-color .12s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .ts-btn:hover {
            transform: translateY(-1px);
            background: rgba(99,102,241,.35);
            border-color: rgba(99,102,241,.45);
            color: #fff;
        }

        [data-bs-theme="light"] .ts-btn {
            background: rgba(99,102,241,.10);
            color: rgba(15,23,42,.95);
            border-color: rgba(99,102,241,.20);
        }
        [data-bs-theme="light"] .ts-btn:hover {
            background: rgba(99,102,241,.14);
            border-color: rgba(99,102,241,.28);
        }

        .list-wrap { max-height: 45vh; overflow: auto; }

        /* table that matches */
        .ts-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: rgba(255,255,255,.06);
            border-bottom: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.85);
            font-weight: 700;
            backdrop-filter: blur(10px);
        }

        .ts-table td, .ts-table th {
            border-color: rgba(255,255,255,.08) !important;
            vertical-align: middle;
            padding-top: .65rem;
            padding-bottom: .65rem;
        }

        .ts-table tbody tr:nth-child(odd) { background: rgba(255,255,255,.015); }
        .ts-table tbody tr:hover { background: rgba(255,255,255,.04); }

        [data-bs-theme="light"] .ts-table thead th {
            background: rgba(15,23,42,.03);
            border-bottom-color: rgba(15,23,42,.08);
            color: rgba(15,23,42,.75);
        }
        [data-bs-theme="light"] .ts-table td, [data-bs-theme="light"] .ts-table th {
            border-color: rgba(15,23,42,.07) !important;
        }
        [data-bs-theme="light"] .ts-table tbody tr:nth-child(odd) { background: rgba(15,23,42,.015); }
        [data-bs-theme="light"] .ts-table tbody tr:hover { background: rgba(15,23,42,.03); }

        .ip_link { cursor: pointer; text-decoration: none; font-weight: 700; }
        .ip_link:hover { text-decoration: underline; }

        .ts-empty {
            padding: 2rem 1rem;
            opacity: .75;
        }

        /* datatables small cleanup */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 999px !important;
            padding: .35rem .75rem !important;
        }
    </style>

    <div class="container-fluid ts-wrap px-4 px-xxl-5">

        {{-- Header --}}
        <div class="ts-card p-3 p-md-4 mb-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="ts-title h3 mb-0">IP Logs</div>
                        <span class="ts-pill">
                        {{ $mode === 'bots' ? 'Bots' : 'Humans' }}
                    </span>
                    </div>
                    <div class="ts-subtitle mt-1">
                        Unique IPs and detailed pageviews by IP for the selected window.
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <span class="ts-pill">1H: <b>{{ $hour1 ?? 0 }}</b></span>
                    <span class="ts-pill">24H: <b>{{ $hour24 ?? 0 }}</b></span>
                </div>
            </div>

            {{-- Range buttons --}}
            <div class="mt-3 d-flex flex-wrap gap-2">
                @foreach([1,6,12,24] as $h)
                    <a class="ts-btn"
                       href="{{ route($routeBase, array_merge($sticky, ['hour' => $h])) }}">
                        <i class="bi bi-clock"></i>
                        {{ $h }} Hour{{ $h > 1 ? 's' : '' }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- IP list --}}
        <div class="ts-card mb-3">
            <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08) !important;">
                <div class="fw-semibold">
                    <i class="bi bi-geo-alt me-2"></i>Top IPs (by hits)
                </div>
                <div class="ts-subtitle">Click “Info” to load recent requests for that IP.</div>
            </div>

            <div class="table-responsive list-wrap">
                <table class="table ts-table table-hover mb-0">
                    <thead>
                    <tr>
                        <th style="min-width:180px">IP Address</th>
                        <th style="min-width:110px" class="text-end">Count</th>
                        <th style="min-width:220px" class="text-end">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse ($ipCountDay as $ipCountD)
                        @php $ipVal = ($ipCountD->ip_raw ?? $ipCountD->ip); @endphp
                        <tr>
                            <td class="text-break">
                                @include('traffic-sentinel::partials.ip-cell', ['ip' => $ipVal])
                            </td>

                            <td class="text-end fw-semibold">{{ number_format($ipCountD->count) }}</td>

                            <td class="text-end text-nowrap">
                                <a class="ip_link me-2" onclick="initTable('{{ $ipCountD->ip }}')">
                                    <i class="bi bi-info-circle me-1"></i>Info
                                </a>

                                <a class="ip_link"
                                   href="{{ route($mode === 'bots' ? 'traffic-sentinel.ip-logs.bots.focus' : 'traffic-sentinel.ip-logs.humans.focus', ['ip' => $ipCountD->ip]) }}">
                                    <i class="bi bi-bullseye me-1"></i>Focus
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center ts-empty">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                No records found for selected range.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Details table --}}
        <div class="ts-card">
            <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08) !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="fw-semibold">
                        <i class="bi bi-list-check me-2"></i>Pageviews Info
                        <span class="ts-pill ms-2" id="data_text">Select an IP</span>
                    </div>
                    <div class="ts-subtitle">Search by path/host/user-agent using DataTables filter.</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table ts-table table-hover mb-0" id="ip_data">
                    <thead>
                    <tr>
                        <th class="text-break">Request</th>
                        <th style="min-width:180px">Date-Time</th>
                        <th style="min-width:160px">Domain</th>
                        <th class="text-break">Agent</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        @include('traffic-sentinel::partials.ip-modal')
    </div>
@endsection

@section('scripts')
    @include('traffic-sentinel::partials.ip-modal-scripts')

    {{-- Only load if your layout doesn’t already include these --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script>
        function initTable(ip) {
            if (!ip) return;

            if ($.fn.DataTable.isDataTable("#ip_data")) {
                $("#ip_data").DataTable().destroy();
                $("#ip_data tbody").empty();
            }

            $('#data_text').text(ip);

            $('#ip_data').DataTable({
                processing: true,
                serverSide: true,
                order: [[1, 'desc']],
                pageLength: 200,
                ajax: '{{ route($routeData) }}' + '?ip=' + encodeURIComponent(ip),
                columns: [
                    { data: 'link', name: 'link' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'domain_text', name: 'domain_text', orderable: false, searchable: false },
                    { data: 'user_agent', name: 'user_agent' }
                ],
            });
        }

        (function () {
            const params = new URLSearchParams(window.location.search);
            const focus = params.get('focus');
            if (focus) initTable(focus);
        })();
    </script>
@endsection
