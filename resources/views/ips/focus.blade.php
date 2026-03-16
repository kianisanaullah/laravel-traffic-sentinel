@extends('traffic-sentinel::layout')

@section('content')

    <div class="ts-card mb-3">

        <div class="p-3 border-bottom">
            <h5 class="mb-0">
                <i class="bi bi-geo-alt me-2"></i>
                IP Focus — {{ $ip }}
            </h5>
        </div>

        <div class="p-3">

            <div class="row">

                <div class="col-md-3">
                    <div class="ts-badge">IP</div>
                    <div class="fw-semibold">{{ $ip }}</div>
                </div>

                <div class="col-md-3">
                    <div class="ts-badge">Country</div>
                    <div>{{ $geo['country'] ?? 'Unknown' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="ts-badge">City</div>
                    <div>{{ $geo['city'] ?? 'Unknown' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="ts-badge">ISP</div>
                    <div>{{ $geo['isp'] ?? 'Unknown' }}</div>
                </div>

            </div>

        </div>

    </div>
    <div class="ts-card mb-3">

        <div class="p-3 border-bottom">
            <strong>Protection Controls</strong>
        </div>

        <div class="p-3">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                <div>

                    @if(!$rule)
                        <span class="ts-badge">
                        <i class="bi bi-question-circle me-1"></i>
                        Unconfigured
                    </span>

                    @elseif($rule->action === 'block')
                        <span class="ts-badge bg-danger">
                        <i class="bi bi-slash-circle me-1"></i>
                        Blocked
                    </span>

                    @elseif($rule->action === 'throttle')
                        <span class="ts-badge bg-warning text-dark">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Throttled
                    </span>

                    @else
                        <span class="ts-badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Monitoring
                    </span>
                    @endif

                </div>


                <div class="d-flex flex-wrap gap-2">

                    {{-- MONITOR --}}
                    <form method="POST" action="{{ route('traffic-sentinel.ips.monitor') }}">
                        @csrf
                        <input type="hidden" name="ip_rule" value="{{ $ip }}">
                        <button class="btn btn-sm btn-success">
                            <i class="bi bi-check-circle me-1"></i>Monitor
                        </button>
                    </form>


                    {{-- BLOCK --}}
                    <form method="POST" action="{{ route('traffic-sentinel.ips.block') }}">
                        @csrf
                        <input type="hidden" name="ip_rule" value="{{ $ip }}">
                        <button class="btn btn-sm btn-danger">
                            <i class="bi bi-slash-circle me-1"></i>Block
                        </button>
                    </form>


                    {{-- THROTTLE --}}
                    <form method="POST"
                          action="{{ route('traffic-sentinel.ips.throttle') }}"
                          class="d-flex gap-2 align-items-center">

                        @csrf

                        <input type="hidden" name="ip_rule" value="{{ $ip }}">

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

                        <button class="btn btn-sm btn-warning">
                            <i class="bi bi-speedometer2 me-1"></i>Throttle
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>


    <div class="ts-card">

        <div class="p-3 border-bottom">
            <strong>Visited Pages</strong>
        </div>

        <div class="table-responsive">

            <table class="table ts-table mb-0">

                <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>URL</th>
                    <th>User Agent</th>
                </tr>
                </thead>

                <tbody>

                @foreach($visits as $visit)

                    <tr>

                        <td>
                            {{ \Carbon\Carbon::parse($visit->last_seen_at)->format('Y-m-d H:i:s') }}
                        </td>

                        <td>
                            @if($visit->type == 'bot')
                                <span class="ts-badge bg-warning text-dark">
                                Bot
                            </span>
                            @else
                                <span class="ts-badge bg-primary">
                                Human
                            </span>
                            @endif
                        </td>

                        <td class="text-break">
                            {{ $visit->url }}
                        </td>

                        <td class="small text-muted text-break">
                            {{ $visit->user_agent }}
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

        <div class="p-3">
            {{ $visits->links('pagination::bootstrap-5') }}
        </div>

    </div>

@endsection
