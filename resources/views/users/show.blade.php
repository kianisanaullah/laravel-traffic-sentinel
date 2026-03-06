<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User {{ $userId }} - Traffic Sentinel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">User #{{ $userId }}</h3>
            <div class="text-muted">Sessions + top pages (humans)</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('traffic-sentinel.users', request()->only(['app','host','range'])) }}">Users</a>
            <a class="btn btn-outline-secondary" href="{{ route('traffic-sentinel.dashboard', request()->only(['app','host','range'])) }}">Dashboard</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Top Pages</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Path</th><th class="text-end">Hits</th></tr></thead>
                        <tbody>
                        @forelse($topPages as $p)
                            <tr>
                                <td class="text-break">{{ $p->path }}</td>
                                <td class="text-end">{{ (int)$p->hits }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">No pageviews</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header fw-semibold">Sessions</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Host</th>
                            <th>IP</th>
                            <th>First Seen</th>
                            <th>Last Seen</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($sessions as $s)
                            <tr>
                                <td>#{{ $s->id }}</td>
                                <td>{{ $s->host }}</td>
                                <td>{{ $s->ip }}</td>
                                <td>{{ $s->first_seen_at }}</td>
                                <td>{{ $s->last_seen_at }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="{{ route('traffic-sentinel.session.journey', ['sessionId' => $s->id]) }}">
                                        Journey
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No sessions</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</div>

</body>
</html>
