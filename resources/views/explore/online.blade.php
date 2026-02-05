@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card">

        {{-- Header --}}
        <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08)">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold">
                    <i class="bi bi-activity me-2"></i>{{ $title }}
                </div>

                <span class="ts-badge">
                    <i class="bi bi-clock me-1"></i>
                    Last {{ $minutes }} minutes
                </span>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0 align-middle" id="tsTable">
                <thead>
                <tr>
                    <th style="min-width:140px">Host</th>
                    <th style="min-width:160px">Visitor</th>
                    <th style="width:120px">Type</th>
                    <th style="width:110px">Device</th>
                    <th style="width:150px">IP</th>
                    <th style="min-width:260px">User Agent</th>
                    <th>Referrer</th>
                    <th style="width:160px" class="text-end">Last Seen</th>
                </tr>
                </thead>

                <tbody>
                @foreach($rows as $r)
                    @php $p = \Kianisanaullah\TrafficSentinel\Support\UserAgentParser::parse($r->user_agent); @endphp

                    <tr>
                        <td class="text-break">
                            <span class="ts-badge"><i class="bi bi-globe2 me-1"></i>{{ $r->host ?? '—' }}</span>
                        </td>

                        <td><code class="small">{{ \Illuminate\Support\Str::limit($r->visitor_key, 18) }}</code></td>

                        <td>
                            @if($r->is_bot)
                                <span class="ts-badge"><i class="bi bi-robot me-1"></i>{{ $r->bot_name ?: 'Bot' }}</span>
                            @else
                                <span class="ts-badge"><i class="bi bi-person me-1"></i>Human</span>
                            @endif
                        </td>

                        <td><span class="ts-pill">{{ ucfirst($r->device_type ?? 'unknown') }}</span></td>

                        <td class="text-break">
                            @if(config('traffic-sentinel.ip.store') === 'hashed')
                                <span class="text-muted small">Hashed</span>
                            @else
                                <code class="small">{{ $r->ip ?: '—' }}</code>
                            @endif
                        </td>

                        <td class="text-break">
                            <div class="fw-semibold">{{ \Kianisanaullah\TrafficSentinel\Support\UserAgentParser::label($p) }}</div>
                            <div class="text-muted small" title="{{ $p['ua'] }}">
                                {{ \Illuminate\Support\Str::limit($p['ua'], 90) }}
                            </div>
                        </td>

                        <td class="text-break text-muted small">{{ $r->referrer ?: '—' }}</td>

                        <td class="text-end text-nowrap">
                            <span class="ts-badge">{{ optional($r->last_seen_at)->format('Y-m-d H:i') }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination (keep only if you are NOT using DataTables client-side) --}}
        @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="p-3 border-top" style="border-color: rgba(255,255,255,.08)">
                {{ $rows->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            TS.initDataTable('#tsTable', {
                order: [[5, 'desc']],
                paging: false,
                info: false,
                lengthChange: false,
            });
        });
    </script>
@endpush
