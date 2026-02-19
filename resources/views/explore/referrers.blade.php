@extends('traffic-sentinel::layout')

@section('content')
    @php
        $qApp   = request('app', $selectedApp ?? '');
        $qHost  = request('host', $selectedHost ?? '');
        $qRange = request('range', $range ?? 'today');
        $refType = request('ref_type', $refType ?? 'outside'); // outside|domain|internal|all

        $qsBase = [
            'app' => $qApp,
            'host' => $qHost,
            'range' => $qRange,
        ];
    @endphp

    <div class="ts-card p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
            <div class="fw-semibold">
                <i class="bi bi-arrow-90deg-left me-2"></i>{{ $title ?? 'Top Referrers' }}
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="ts-badge">Range: {{ $range === 'today' ? 'Today' : "Last $days days" }}</span>
            </div>
        </div>

        {{-- Referrer Type Filter --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            @php
                $btn = function ($type, $label, $icon) use ($qsBase, $refType) {
                    $isActive = $refType === $type;
                    $url = route('traffic-sentinel.referrers', array_merge($qsBase, ['ref_type' => $type]));
                    return '<a href="'.$url.'" class="text-decoration-none">
                        <span class="ts-pill '.($isActive ? 'fw-semibold' : '').'" style="'.($isActive ? 'border-color: rgba(99,102,241,.35); background: rgba(99,102,241,.06);' : '').'">
                            <i class="bi '.$icon.' me-1"></i>'.$label.'
                        </span>
                    </a>';
                };
            @endphp

            {!! $btn('outside', 'Outside Referrers', 'bi-box-arrow-up-right') !!}
            {!! $btn('domain', 'Same Domain (Subdomains)', 'bi-diagram-3') !!}
            {!! $btn('internal', 'Internal (Same Host)', 'bi-house-door') !!}
            {!! $btn('all', 'All', 'bi-collection') !!}
        </div>

        <div class="table-responsive">
            <table class="table ts-table table-hover mb-0">
                <thead>
                <tr>
                    <th>Referrer</th>
                    <th class="text-end">Hits</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    @php
                        $to = route('traffic-sentinel.referrers.show', array_merge($qsBase, [
                            'ref_type' => $refType,
                            'referrer' => $r->referrer,
                        ]));
                    @endphp
                    <tr>
                        <td class="text-break">
                            <span class="ts-badge me-2"><i class="bi bi-globe2"></i></span>
                            {{ $r->referrer }}
                        </td>
                        <td class="text-end fw-semibold">{{ (int) $r->hits }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ $to }}">
                                View sessions <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center ts-empty">No records</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
