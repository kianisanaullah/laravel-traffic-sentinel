<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Journey #{{ $session->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Session Journey #{{ $session->id }}</h3>
            <div class="text-muted">
                User: {{ $session->user_id ?: '—' }} |
                Visitor: {{ $session->visitor_key }} |
                IP: {{ $session->ip }} |
                Host: {{ $session->host }}
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($session->user_id)
                <a class="btn btn-outline-secondary"
                   href="{{ route('traffic-sentinel.users.show', array_merge(['userId' => $session->user_id], request()->only(['app','host','range']))) }}">
                    User
                </a>
            @endif
            <a class="btn btn-outline-secondary"
               href="{{ route('traffic-sentinel.dashboard', request()->only(['app','host','range'])) }}">
                Dashboard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>Time</th>
                    <th>Method</th>
                    <th>Path</th>
                    <th>Status</th>
                    <th class="text-end">Duration (ms)</th>
                </tr>
                </thead>
                <tbody>
                @forelse($pageviews as $pv)
                    <tr>
                        <td>{{ $pv->viewed_at }}</td>
                        <td>{{ $pv->method }}</td>
                        <td class="text-break">
                            <div class="fw-semibold">{{ $pv->path }}</div>
                            @if($pv->full_url)
                                <div class="text-muted small">{{ $pv->full_url }}</div>
                            @endif
                        </td>
                        <td>{{ $pv->status_code }}</td>
                        <td class="text-end">{{ (int)$pv->duration_ms }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No pageviews in this session</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $pageviews->links() }}
    </div>
</div>

</body>
</html>
