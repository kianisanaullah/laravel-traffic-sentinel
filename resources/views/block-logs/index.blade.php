@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card">

        {{-- HEADER --}}
        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold">
                    <i class="bi bi-shield-exclamation me-2"></i>Blocked Requests
                </div>

                <span class="ts-badge">
                <i class="bi bi-shield-fill-exclamation me-1"></i>
                {{ $logs->total() }} Attempts
            </span>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <form method="GET" class="row g-2 align-items-end">

                <div class="col-md-4">
                    <label class="form-label small mb-1">IP Address</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                           class="form-control form-control-sm"
                           placeholder="Search IP...">
                </div>

                <div class="col-md-3">
                    <label class="form-label small mb-1">Reason</label>
                    <select name="reason" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="empty_user_agent" @selected(request('reason')=='empty_user_agent')>Empty UA</option>
                        <option value="bot_rule_block" @selected(request('reason')=='bot_rule_block')>Bot Block</option>
                        <option value="ip_already_blocked" @selected(request('reason')=='ip_already_blocked')>IP Blocked</option>
                    </select>
                </div>

                <div class="col-md-5 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>

                    <a href="{{ route('traffic-sentinel.block-logs.index') }}"
                       class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle" id="logsTable">

                <thead>
                <tr>
                    <th style="min-width:180px">IP</th>
                    <th style="width:140px">Bot</th>
                    <th style="width:160px">Reason</th>
                    <th style="width:100px">Hits</th>
                    <th style="width:200px">Last Hit</th>
                    <th style="min-width:250px">URL</th>
                </tr>
                </thead>

                <tbody>
                @foreach($logs as $log)
                    <tr>

                        {{-- IP --}}
                        <td>
                            @include('traffic-sentinel::partials.ip-cell', ['ip' => $log->ip])
                        </td>

                        {{-- BOT --}}
                        <td>
                            @if($log->bot_name)
                                <span class="ts-badge">
                                <i class="bi bi-robot me-1"></i>{{ $log->bot_name }}
                            </span>
                            @else
                                <span class="ts-badge">
                                <i class="bi bi-person me-1"></i>Unknown
                            </span>
                            @endif
                        </td>

                        {{-- REASON --}}
                        <td>
                            @if($log->reason === 'empty_user_agent')
                                <span class="ts-badge bg-danger">Empty UA</span>
                            @elseif($log->reason === 'bot_rule_block')
                                <span class="ts-badge bg-warning text-dark">Bot Rule</span>
                            @elseif($log->reason === 'ip_already_blocked')
                                <span class="ts-badge bg-secondary">Repeat</span>
                            @else
                                <span class="ts-badge">{{ $log->reason }}</span>
                            @endif
                        </td>

                        {{-- HITS --}}
                        <td>
                            <span class="ts-pill">{{ number_format($log->hits) }}</span>
                        </td>

                        {{-- LAST HIT --}}
                        <td class="text-nowrap">
                        <span class="ts-badge">
                            {{ $log->last_hit_at ? \Carbon\Carbon::parse($log->last_hit_at)->format('Y-m-d H:i') : '—' }}
                        </span>
                        </td>

                        {{-- URL --}}
                        <td class="text-break small">
                            {{ $log->path }}
                        </td>

                    </tr>
                @endforeach
                </tbody>

            </table>

            {{-- PAGINATION --}}
            <div class="p-3 border-top" style="border-color: rgba(255,255,255,.08)">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>

        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#logsTable', {
                order: [[4, 'desc']],
                paging: false,
                info: false,
                lengthChange: false,
            });
        });
    </script>
@endpush
