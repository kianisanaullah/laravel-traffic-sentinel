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
            <table class="table ts-table table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th style="min-width:140px">Host</th>
                    <th style="min-width:160px">Visitor</th>
                    <th style="width:120px">Type</th>
                    <th style="width:110px">Device</th>
                    <th>Referrer</th>
                    <th style="width:160px" class="text-end">Last Seen</th>
                </tr>
                </thead>

                <tbody>
                @forelse($rows as $r)
                    <tr>
                        {{-- Host --}}
                        <td class="text-break">
                        <span class="ts-badge">
                            <i class="bi bi-globe2 me-1"></i>
                            {{ $r->host ?? '—' }}
                        </span>
                        </td>

                        {{-- Visitor --}}
                        <td>
                            <code class="small">{{ Str::limit($r->visitor_key, 18) }}</code>
                        </td>

                        {{-- Bot / Human --}}
                        <td>
                            @if($r->is_bot)
                                <span class="ts-badge">
                                <i class="bi bi-robot me-1"></i>
                                {{ $r->bot_name ?: 'Bot' }}
                            </span>
                            @else
                                <span class="ts-badge">
                                <i class="bi bi-person me-1"></i>
                                Human
                            </span>
                            @endif
                        </td>

                        {{-- Device --}}
                        <td>
                        <span class="ts-pill">
                            {{ ucfirst($r->device_type ?? 'unknown') }}
                        </span>
                        </td>

                        {{-- Referrer --}}
                        <td class="text-break text-muted small">
                            {{ $r->referrer ?: '—' }}
                        </td>

                        {{-- Last Seen --}}
                        <td class="text-end text-nowrap">
                        <span class="ts-badge">
                            {{ optional($r->last_seen_at)->format('Y-m-d H:i') }}
                        </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center ts-empty">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No active sessions
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="p-3 border-top" style="border-color: rgba(255,255,255,.08)">
                {{ $rows->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </div>
@endsection
