<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Traffic Sentinel - Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Users</h3>
        <a class="btn btn-outline-secondary" href="{{ route('traffic-sentinel.dashboard', request()->only(['app','host','range'])) }}">Back</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th class="text-end">Pageviews</th>
                    <th class="text-end">Sessions</th>
                    <th>Last Seen</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>
                            <a href="{{ route('traffic-sentinel.users.show', array_merge(['userId' => $r->user_id], request()->only(['app','host','range']))) }}">
                                {{ $r->user_id }}
                            </a>
                        </td>
                        <td class="text-end">{{ (int)$r->pageviews }}</td>
                        <td class="text-end">{{ (int)$r->sessions }}</td>
                        <td>{{ $r->last_seen_at }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ route('traffic-sentinel.users.show', array_merge(['userId' => $r->user_id], request()->only(['app','host','range']))) }}">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No user activity found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $rows->links() }}
    </div>
</div>

</body>
</html>
