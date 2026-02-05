@extends('traffic-sentinel::layout')

@section('content')
<div class="ts-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold"><i class="bi bi-signpost-2 me-2"></i>{{ $title }}</div>
        <span class="ts-badge">Range: {{ $range === 'today' ? 'Today' : "Last $days days" }}</span>
    </div>

    <div class="table-responsive">
        <table class="table ts-table table-hover mb-0">
            <thead>
            <tr>
                <th>Path</th>
                <th class="text-end">Hits</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="text-break">{{ $r->path }}</td>
                    <td class="text-end fw-semibold">{{ (int) $r->hits }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-light"
                           href="{{ route('traffic-sentinel.pages.path', ['path' => $r->path, 'host' => request('host'), 'range' => request('range','today')]) }}">
                            View logs
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center ts-empty">No records</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->withQueryString()->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
