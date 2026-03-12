@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card">

        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold">
                    <i class="bi bi-geo-alt me-2"></i>IP Management
                </div>

                <span class="ts-badge">
                <i class="bi bi-shield-check me-1"></i>
                {{ $ips->count() }} IPs Detected
            </span>
            </div>
        </div>
        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">IP Address</label>
                    <input
                            type="text"
                            name="ip"
                            value="{{ request('ip') }}"
                            class="form-control form-control-sm"
                            placeholder="Search IP..."
                    >
                </div>

                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="human" @selected(request('type') === 'human')>Human</option>
                        <option value="bot" @selected(request('type') === 'bot')>Bot</option>
                        <option value="mixed" @selected(request('type') === 'mixed')>Mixed</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="unconfigured" @selected(request('status') === 'unconfigured')>Unconfigured</option>
                        <option value="monitor" @selected(request('status') === 'monitor')>Monitoring</option>
                        <option value="throttle" @selected(request('status') === 'throttle')>Throttled</option>
                        <option value="block" @selected(request('status') === 'block')>Blocked</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>

                    <a href="{{ route('traffic-sentinel.ips.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle" id="ipsTable">
                <thead>
                <tr>
                    <th style="min-width:200px">IP</th>
                    <th style="width:120px">Type</th>
                    <th style="width:120px">Sessions</th>
                    <th style="width:180px">Last Seen</th>
                    <th style="width:220px">Status</th>
                    <th style="min-width:360px">Actions</th>
                </tr>
                </thead>

                <tbody>
                @foreach($ips as $row)
                    @php
                        $rule = $rules[$row->ip] ?? null;

                        $limitParts = [];
                        if ($rule && $rule->limit_per_minute) $limitParts[] = $rule->limit_per_minute . '/min';
                        if ($rule && $rule->limit_per_hour) $limitParts[] = $rule->limit_per_hour . '/hour';
                        if ($rule && $rule->limit_per_day) $limitParts[] = $rule->limit_per_day . '/day';

                        $limitLabel = implode(' • ', $limitParts);
                    @endphp

                    <tr>
                        <td class="text-break">
                            @include('traffic-sentinel::partials.ip-cell', ['ip' => $row->ip])
                        </td>

                        <td class="text-nowrap">
                            @if($row->traffic_type === 'mixed')
                                <span class="ts-badge">
                                <i class="bi bi-shuffle me-1"></i>Mixed
                            </span>
                            @elseif($row->traffic_type === 'bot')
                                <span class="ts-badge">
                                <i class="bi bi-robot me-1"></i>Bot
                            </span>
                            @else
                                <span class="ts-badge">
                                <i class="bi bi-person me-1"></i>Human
                            </span>
                            @endif
                        </td>

                        <td>
                            <span class="ts-pill">{{ number_format((int) $row->sessions) }}</span>
                        </td>

                        <td class="text-nowrap">
                        <span class="ts-badge">
                            {{ $row->last_seen ? \Carbon\Carbon::parse($row->last_seen)->format('Y-m-d H:i') : '—' }}
                        </span>
                        </td>

                        <td>
                            @if(!$rule)
                                <span class="ts-badge">
                                <i class="bi bi-question-circle me-1"></i>Unconfigured
                            </span>
                            @elseif($rule->action === 'block')
                                <span class="ts-badge bg-danger">
                                <i class="bi bi-slash-circle me-1"></i>Blocked
                            </span>
                            @elseif($rule->action === 'throttle')
                                <span class="ts-badge bg-warning text-dark">
                                <i class="bi bi-speedometer2 me-1"></i>
                                Throttled{{ $limitLabel ? ' (' . $limitLabel . ')' : '' }}
                            </span>
                            @else
                                <span class="ts-badge bg-success">
                                <i class="bi bi-check-circle me-1"></i>Monitoring
                            </span>
                            @endif
                        </td>

                        <td>
                            <div class="d-flex flex-wrap gap-1">

                                <form method="POST" action="{{ route('traffic-sentinel.ips.monitor') }}">
                                    @csrf
                                    <input type="hidden" name="ip_rule" value="{{ $row->ip }}">
                                    <button class="btn btn-sm btn-success" title="Monitor">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('traffic-sentinel.ips.block') }}">
                                    @csrf
                                    <input type="hidden" name="ip_rule" value="{{ $row->ip }}">
                                    <button class="btn btn-sm btn-danger" title="Block">
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('traffic-sentinel.ips.throttle') }}"
                                      class="d-flex flex-wrap gap-1 align-items-center">
                                    @csrf
                                    <input type="hidden" name="ip_rule" value="{{ $row->ip }}">

                                    <input type="number"
                                           name="rpm"
                                           placeholder="RPM"
                                           class="form-control form-control-sm"
                                           style="width:80px">

                                    <input type="number"
                                           name="rph"
                                           placeholder="RPH"
                                           class="form-control form-control-sm"
                                           style="width:80px">

                                    <input type="number"
                                           name="rpd"
                                           placeholder="RPD"
                                           class="form-control form-control-sm"
                                           style="width:90px">

                                    <button class="btn btn-sm btn-warning" title="Throttle">
                                        <i class="bi bi-speedometer2"></i>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>
            @if($ips instanceof \Illuminate\Contracts\Pagination\Paginator || $ips instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                <div class="p-3 border-top" style="border-color: rgba(255,255,255,.08)">
                    {{ $ips->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#ipsTable', {
                order: [[2, 'desc']],
                paging: false,
                info: false,
                lengthChange: false,
            });
        });
    </script>
@endpush
