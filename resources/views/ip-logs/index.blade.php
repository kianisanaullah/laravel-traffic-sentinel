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

        $routeFocus = $mode === 'bots'
            ? 'traffic-sentinel.ip-logs.bots.focus'
            : 'traffic-sentinel.ip-logs.humans.focus';

        $label = $mode === 'bots' ? 'Bots' : 'Humans';
    @endphp

    <div class="ts-card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <div class="fw-semibold fs-3">
                    <i class="bi bi-router me-2"></i>IP Logs
                    <span class="ts-badge ms-2">{{ $label }}</span>
                </div>
                <div class="text-muted">
                    Unique IPs and detailed pageviews by IP for the selected window.
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <span class="ts-badge">1H: {{ number_format($hour1 ?? 0) }}</span>
                <span class="ts-badge">24H: {{ number_format($hour24 ?? 0) }}</span>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @foreach([1,6,12,24] as $h)
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route($routeBase, ['hour' => $h]) }}">
                    <i class="bi bi-clock me-1"></i>{{ $h }} Hour{{ $h > 1 ? 's' : '' }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="ts-card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">
                <i class="bi bi-geo-alt me-2"></i>Top IPs
            </div>
            <span class="ts-badge">By Hits</span>
        </div>

        <div class="table-responsive">
            <table id="tsIpTable" class="table ts-table table-hover mb-0">
                <thead>
                <tr>
                    <th>IP Address</th>
                    <th class="text-end">Count</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($ipCountDay as $ipCountD)
                    @php
                        $ipVal = $ipCountD->ip_raw ?: $ipCountD->ip;
                    @endphp
                    <tr>
                        <td class="text-break">
                            @include('traffic-sentinel::partials.ip-cell', ['ip' => $ipVal])
                        </td>
                        <td class="text-end fw-semibold">
                            {{ number_format($ipCountD->count) }}
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="javascript:void(0)" class="me-3 text-decoration-none" onclick="initTable('{{ $ipCountD->ip }}')">
                                <i class="bi bi-info-circle me-1"></i>Info
                            </a>
                            <a href="{{ route($routeFocus, ['ip' => $ipCountD->ip]) }}" class="text-decoration-none">
                                <i class="bi bi-bullseye me-1"></i>Focus
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center ts-empty">No records found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ts-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="fw-semibold">
                <i class="bi bi-list-check me-2"></i>Pageviews Info
                <span id="data_text" class="ts-badge ms-2">Select an IP</span>
            </div>
        </div>

        <div class="table-responsive">
            <table id="ip_data" class="table ts-table table-hover mb-0">
                <thead>
                <tr>
                    <th>Request</th>
                    <th>Date-Time</th>
                    <th>Domain</th>
                    <th>Agent</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    @include('traffic-sentinel::partials.ip-modal')
@endsection

@push('scripts')
    @include('traffic-sentinel::partials.ip-modal-scripts')

    <script>
        function initTable(ip) {
            if (!ip) return;

            if ($.fn.DataTable.isDataTable('#ip_data')) {
                $('#ip_data').DataTable().destroy();
                $('#ip_data tbody').empty();
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
                columnDefs: [
                    { targets: 0, render: function (data) { return data; } }
                ]
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#tsIpTable', {
                order: [[1, 'desc']],
                paging: false,
                info: false,
                searching: false,
                lengthChange: false,
            });

            const params = new URLSearchParams(window.location.search);
            const focus = params.get('focus');
            if (focus) initTable(focus);
        });
    </script>
@endpush
