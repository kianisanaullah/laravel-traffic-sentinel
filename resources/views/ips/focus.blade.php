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

            <div class="row g-3">

                <div class="col-lg-3 col-md-6">
                    <div class="ts-badge">IP</div>
                    <div class="fw-semibold">{{ $ip }}</div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="ts-badge">Country</div>
                    <div>{{ $geo['country'] ?? 'Unknown' }}</div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="ts-badge">City</div>
                    <div>{{ $geo['city'] ?? 'Unknown' }}</div>
                </div>

                <div class="col-lg-3 col-md-6">
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

        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">

            <strong>
                Visited Pages
            </strong>

            <span class="ts-badge">
{{ $visits->total() }} Requests
</span>

        </div>


        <div class="table-responsive">

            <table class="table ts-table table-hover align-middle mb-0">

                <thead>

                <tr>

                    <th style="width:150px">Time</th>

                    <th style="width:80px">Type</th>

                    <th style="width:70px">Method</th>

                    <th>Path</th>

                    <th style="width:90px">Status</th>

                    <th style="width:110px">Duration</th>

                    <th>Route</th>

                    <th>Referrer</th>

                    <th style="width:180px">Session</th>

                    <th style="width:160px">User/Bot</th>

                </tr>

                </thead>


                <tbody>

                @foreach($visits as $visit)

                    <tr>

                        <td class="small text-nowrap">
                            {{ \Carbon\Carbon::parse($visit->viewed_at)->format('Y-m-d H:i:s') }}
                        </td>


                        <td>

                            @if($visit->type === 'bot')

                                <span class="ts-badge bg-warning text-dark">
<i class="bi bi-robot me-1"></i>
Bot
</span>

                            @else

                                <span class="ts-badge bg-primary">
<i class="bi bi-person me-1"></i>
Human
</span>

                            @endif

                        </td>


                        <td>
<span class="ts-pill">
{{ $visit->method }}
</span>
                        </td>


                        <td class="text-break">

                            <div class="fw-semibold small">
                                {{ $visit->path }}
                            </div>

                            @if($visit->full_url)
                                <div class="text-muted small">
                                    {{ $visit->full_url }}
                                </div>
                            @endif

                        </td>


                        <td>

                            @if($visit->status_code)

                                <span class="ts-pill">
{{ $visit->status_code }}
</span>

                            @endif

                        </td>


                        <td class="small text-muted">

                            @if($visit->duration_ms)
                                {{ number_format($visit->duration_ms) }} ms
                            @endif

                        </td>


                        <td class="small text-muted text-break">
                            {{ $visit->route_name ?? '—' }}
                        </td>


                        <td class="small text-break">

                            @if($visit->referrer)

                                <span class="text-muted">
{{ Str::limit($visit->referrer, 80) }}
</span>

                            @else
                                —
                            @endif

                        </td>


                        <td class="small text-muted">

                            @if($visit->session_id)
                                {{ Str::limit($visit->session_id, 14) }}
                            @endif

                        </td>


                        <td class="small text-break">

                            @if($visit->type === 'bot')

                                <span class="ts-badge bg-warning text-dark">
{{ $visit->bot_name ?? 'Bot' }}
</span>

                            @else

                                @if($visit->user_id)

                                    <span class="ts-badge bg-success">
User #{{ $visit->user_id }}
</span>

                                @else

                                    Visitor {{ Str::limit($visit->visitor_key, 10) }}

                                @endif

                            @endif

                        </td>


                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>


        <div class="p-3 border-top">

            {{ $visits->links('pagination::bootstrap-5') }}

        </div>

    </div>

@endsection
