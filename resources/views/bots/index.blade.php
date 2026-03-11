@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card">

        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold">
                    <i class="bi bi-robot me-2"></i>Bot Management
                </div>

                <span class="ts-badge">
                    <i class="bi bi-shield-check me-1"></i>
                    {{ $bots->count() }} Bots Detected
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle" id="botsTable">
                <thead>
                <tr>
                    <th style="min-width:160px">Bot</th>
                    <th style="width:120px">Sessions</th>
                    <th style="width:140px">Unique IPs</th>
                    <th style="width:180px">Last Seen</th>
                    <th style="width:180px">Status</th>
                    <th style="width:260px">Actions</th>
                </tr>
                </thead>

                <tbody>
                @foreach($bots as $bot)

                    @php
                        $rule = $rules[$bot->bot_name] ?? null;

                        $limitParts = [];
                        if ($rule && $rule->limit_per_minute) $limitParts[] = $rule->limit_per_minute . '/min';
                        if ($rule && $rule->limit_per_hour)   $limitParts[] = $rule->limit_per_hour . '/hour';
                        if ($rule && $rule->limit_per_day)    $limitParts[] = $rule->limit_per_day . '/day';

                        $limitLabel = implode(' • ', $limitParts);
                    @endphp

                    <tr>
                        <td>
                            <span class="ts-badge">
                                <i class="bi bi-robot me-1"></i>
                                {{ $bot->bot_name ?: 'Unknown Bot' }}
                            </span>
                        </td>

                        <td>
                            <span class="ts-pill">{{ number_format((int) $bot->sessions) }}</span>
                        </td>

                        <td>
                            <span class="ts-pill">{{ number_format((int) ($bot->ips ?? 0)) }}</span>
                        </td>

                        <td class="text-nowrap">
                            <span class="ts-badge">
                                {{ $bot->last_seen ? \Carbon\Carbon::parse($bot->last_seen)->format('Y-m-d H:i') : '—' }}
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

                                <form method="POST" action="{{ route('traffic-sentinel.bots.monitor') }}">
                                    @csrf
                                    <input type="hidden" name="bot" value="{{ $bot->bot_name }}">
                                    <button class="btn btn-sm btn-success" title="Monitor">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('traffic-sentinel.bots.block') }}">
                                    @csrf
                                    <input type="hidden" name="bot" value="{{ $bot->bot_name }}">
                                    <button class="btn btn-sm btn-danger" title="Block">
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('traffic-sentinel.bots.throttle') }}"
                                      class="d-flex gap-1">
                                    @csrf
                                    <input type="hidden" name="bot" value="{{ $bot->bot_name }}">

                                    <input
                                            type="number"
                                            name="rpm"
                                            placeholder="RPM"
                                            class="form-control form-control-sm"
                                            style="width:80px"
                                    >

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
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#botsTable', {
                order: [[1, 'desc']],
                paging: false,
                info: false,
                lengthChange: false,
            });
        });
    </script>
@endpush
