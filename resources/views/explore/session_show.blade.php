@extends('traffic-sentinel::layout')

@section('content')
    <div class="row g-3">

        {{-- Session summary --}}
        <div class="col-12 col-lg-4">
            <div class="ts-card p-3 h-100">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-person-lines-fill me-1"></i>
                    Session Overview
                </div>

                <div class="small text-muted mb-2">Visitor</div>
                <code class="d-block mb-3">{{ $row->visitor_key }}</code>

                <div class="mb-2">
                <span class="ts-badge">
                    {{ $row->is_bot ? 'Bot' : 'Human' }}
                </span>
                    @if($row->bot_name)
                        <span class="ts-badge ms-1">
                        <i class="bi bi-robot me-1"></i>{{ $row->bot_name }}
                    </span>
                    @endif
                </div>

                <div class="small text-muted mt-3">IP Address</div>
                <div class="fw-semibold"> @include('traffic-sentinel::partials.ip-cell', ['ip' => ($r->ip_raw ?? $r->ip)])</div>

                <div class="small text-muted mt-3">Device</div>
                <div>{{ ucfirst($row->device_type ?? 'unknown') }}</div>

                <div class="small text-muted mt-3">User Agent</div>
                <div class="small text-break">
                    {{ $row->user_agent ?? '—' }}
                </div>

                <hr class="opacity-25">

                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small text-muted">First seen</div>
                        <div>{{ $row->first_seen_at }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Last seen</div>
                        <div>{{ $row->last_seen_at }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Geo --}}
        <div class="col-12 col-lg-4">
            <div class="ts-card p-3 h-100">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-globe2 me-1"></i>
                    Geo Information
                </div>

                @if(!empty($geo))
                    <table class="table table-sm ts-table mb-0">
                        <tr>
                            <th>Country</th>
                            <td>{{ $geo['country'] ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td>{{ $geo['city'] ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Region</th>
                            <td>{{ $geo['state_name'] ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>ISP</th>
                            <td>{{ $geo['isp'] ?? '—' }}</td>
                        </tr>
                    </table>
                @else
                    <div class="ts-empty">
                        Geo lookup not available
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent pageviews --}}
        <div class="col-12 col-lg-4">
            <div class="ts-card p-3 h-100">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-eye me-1"></i>
                    Recent Pageviews
                </div>

                <div class="table-responsive">
                    <table class="table table-sm ts-table mb-0">
                        <thead>
                        <tr>
                            <th>Method</th>
                            <th>Path</th>
                            <th class="text-end">ms</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($row->pageviews as $pv)
                            <tr>
                                <td>
                                    <span class="ts-pill">{{ $pv->method }}</span>
                                </td>
                                <td class="text-break">
                                    {{ $pv->path }}
                                </td>
                                <td class="text-end">
                                    {{ $pv->duration_ms ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center ts-empty">
                                    No pageviews
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
