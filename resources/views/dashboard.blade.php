<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Traffic Sentinel Dashboard</title>

    @php
        $pubIco  = public_path('vendor/traffic-sentinel/favicon.ico');
        $pubPng  = public_path('vendor/traffic-sentinel/favicon.png');
        $pubSvg  = public_path('vendor/traffic-sentinel/favicon.svg');
        $pubLogo = public_path('vendor/traffic-sentinel/logo.svg');

        $faviconIco = file_exists($pubIco)
            ? asset('vendor/traffic-sentinel/favicon.ico')
            : route('traffic-sentinel.asset', ['file' => 'favicon.ico']);

        $faviconPng = file_exists($pubPng)
            ? asset('vendor/traffic-sentinel/favicon.png')
            : route('traffic-sentinel.asset', ['file' => 'favicon.png']);

        $faviconSvg = file_exists($pubSvg)
            ? asset('vendor/traffic-sentinel/favicon.svg')
            : route('traffic-sentinel.asset', ['file' => 'favicon.svg']);

        $logoSrc = file_exists($pubLogo)
            ? asset('vendor/traffic-sentinel/logo.svg')
            : route('traffic-sentinel.asset', ['file' => 'logo.svg']);
    @endphp

    {{-- Favicons --}}
    <link rel="icon" href="{{ $faviconIco }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ $faviconSvg }}">
    <link rel="icon" type="image/png" href="{{ $faviconPng }}">
    <link rel="apple-touch-icon" href="{{ $faviconPng }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root{
            --ts-bg: #0b1220;
            --ts-card-border: rgba(255,255,255,.12);
            --ts-soft: rgba(255,255,255,.65);
            --ts-muted: rgba(255,255,255,.55);
            --ts-line: rgba(255,255,255,.12);
            --ts-radius: 16px;
        }

        body{
            background:
                    radial-gradient(1200px 500px at 10% -10%, rgba(99,102,241,.35), transparent 60%),
                    radial-gradient(900px 450px at 90% 0%, rgba(16,185,129,.25), transparent 55%),
                    radial-gradient(900px 450px at 20% 100%, rgba(236,72,153,.20), transparent 55%),
                    var(--ts-bg);
            color: #e8eefc;
        }

        .ts-shell{ max-width: 1180px; }

        .ts-header{
            border: 1px solid var(--ts-line);
            background: rgba(255,255,255,.04);
            border-radius: var(--ts-radius);
            backdrop-filter: blur(10px);
        }

        .ts-title{
            letter-spacing: .2px;
            font-weight: 700;
            line-height: 1.1;
        }

        .ts-subtitle{ color: var(--ts-muted); font-size: .95rem; }

        .ts-card{
            border-radius: var(--ts-radius);
            border: 1px solid var(--ts-card-border);
            background: rgba(255,255,255,.06);
            box-shadow: 0 18px 40px rgba(0,0,0,.25);
            backdrop-filter: blur(10px);
        }

        .ts-kpi{
            border-radius: var(--ts-radius);
            border: 1px solid var(--ts-card-border);
            background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.05));
            box-shadow: 0 18px 40px rgba(0,0,0,.25);
            backdrop-filter: blur(10px);
            overflow: hidden;
            position: relative;
        }

        .ts-kpi::before{
            content:"";
            position:absolute;
            inset:-40px -40px auto auto;
            width: 160px;
            height: 160px;
            border-radius: 999px;
            opacity: .55;
            background: radial-gradient(circle at 30% 30%, rgba(99,102,241,.9), transparent 60%);
        }
        .ts-kpi.green::before{ background: radial-gradient(circle at 30% 30%, rgba(16,185,129,.9), transparent 60%); }
        .ts-kpi.pink::before{ background: radial-gradient(circle at 30% 30%, rgba(236,72,153,.9), transparent 60%); }
        .ts-kpi.orange::before{ background: radial-gradient(circle at 30% 30%, rgba(245,158,11,.95), transparent 60%); }

        .ts-kpi .label{ color: var(--ts-muted); font-size: .9rem; }
        .ts-kpi .value{ font-size: 2.1rem; font-weight: 800; letter-spacing: .2px; }
        .ts-kpi .hint{ color: var(--ts-muted); font-size: .85rem; }

        .ts-pill{
            border: 1px solid var(--ts-line);
            background: rgba(255,255,255,.04);
            color: var(--ts-soft);
            border-radius: 999px;
            padding: .35rem .6rem;
            font-size: .85rem;
        }

        .ts-table thead th{
            color: rgba(255,255,255,.75);
            font-weight: 600;
            border-bottom: 1px solid var(--ts-line);
            background: rgba(255,255,255,.03);
        }
        .ts-table td, .ts-table th{
            border-color: rgba(255,255,255,.08) !important;
            vertical-align: middle;
        }
        .ts-table tbody tr:hover{ background: rgba(255,255,255,.04); }

        .ts-badge{
            font-size: .78rem;
            border-radius: 999px;
            padding: .25rem .55rem;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.06);
            color: rgba(255,255,255,.85);
        }

        .ts-empty{ padding: 2.2rem 1rem; color: var(--ts-muted); }
        .ts-foot{ color: rgba(255,255,255,.55); font-size: .85rem; }

        .ts-logo{ filter: drop-shadow(0 6px 14px rgba(99,102,241,.35)); }

        /* Light theme overrides */
        [data-bs-theme="light"] body{ background: #f6f7fb; color: #0f172a; }
        [data-bs-theme="light"] .ts-header,
        [data-bs-theme="light"] .ts-card,
        [data-bs-theme="light"] .ts-kpi{
            background: #fff;
            border-color: rgba(15, 23, 42, .08);
            box-shadow: 0 16px 35px rgba(15,23,42,.08);
        }
        [data-bs-theme="light"] .ts-subtitle,
        [data-bs-theme="light"] .ts-kpi .label,
        [data-bs-theme="light"] .ts-kpi .hint,
        [data-bs-theme="light"] .ts-foot{
            color: rgba(15,23,42,.6);
        }
        [data-bs-theme="light"] .ts-table thead th{
            color: rgba(15,23,42,.7);
            background: rgba(15,23,42,.03);
            border-bottom-color: rgba(15,23,42,.08);
        }
        [data-bs-theme="light"] .ts-table td, [data-bs-theme="light"] .ts-table th{
            border-color: rgba(15,23,42,.07) !important;
        }
        [data-bs-theme="light"] .ts-table tbody tr:hover{ background: rgba(15,23,42,.03); }
        [data-bs-theme="light"] .ts-pill{
            border-color: rgba(15,23,42,.10);
            background: rgba(15,23,42,.03);
            color: rgba(15,23,42,.75);
        }
        [data-bs-theme="light"] .ts-badge{
            border-color: rgba(15,23,42,.12);
            background: rgba(15,23,42,.03);
            color: rgba(15,23,42,.75);
        }
    </style>
</head>

<body class="py-4">
<div class="container ts-shell">

    {{-- Header --}}
    <div class="ts-header p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">

            <div class="d-flex align-items-center gap-3">
                <img src="{{ $logoSrc }}" alt="Traffic Sentinel" height="44" class="ts-logo">

                <div>
                    <div class="ts-title h4 mb-1">Traffic Sentinel</div>
                    <div class="ts-subtitle">
                        Visitors + Bots monitoring
                        @if(!empty($selectedHost))
                            <span class="ms-2 ts-badge"><i class="bi bi-globe2 me-1"></i>{{ $selectedHost }}</span>
                        @else
                            <span class="ms-2 ts-badge"><i class="bi bi-diagram-3 me-1"></i>All Hosts</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="ts-pill">
                    <i class="bi bi-clock me-1"></i>
                    Online window: {{ config('traffic-sentinel.online_minutes', 5) }} min
                </span>

                <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
                    {{-- Host filter --}}
                    <select name="host" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 210px;">
                        <option value="" @selected(empty($selectedHost))>All hosts</option>
                        @foreach(($hosts ?? []) as $h)
                            <option value="{{ $h }}" @selected($selectedHost === $h)>{{ $h }}</option>
                        @endforeach
                    </select>

                    {{-- Range filter --}}
                    <select name="range" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 160px;">
                        <option value="today" @selected($range === 'today')>Today</option>
                        <option value="7" @selected($range === '7')>Last 7 days</option>
                        <option value="30" @selected($range === '30')>Last 30 days</option>
                    </select>

                    <button type="button" class="btn btn-sm btn-outline-secondary" id="tsThemeBtn" title="Toggle theme">
                        <i class="bi bi-moon-stars"></i>
                    </button>

                    <noscript><button class="btn btn-sm btn-primary">Apply</button></noscript>
                </form>
            </div>
        </div>
    </div>

    {{-- KPI grid --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="ts-kpi p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="label">Online Humans</div>
                        <div class="value">{{ $onlineHumans }}</div>
                        <div class="hint">Active in last {{ config('traffic-sentinel.online_minutes', 5) }} min</div>
                    </div>
                    <span class="ts-badge"><i class="bi bi-person-check me-1"></i>Live</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="ts-kpi green p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="label">Online Bots</div>
                        <div class="value">{{ $onlineBots }}</div>
                        <div class="hint">Crawlers / tools</div>
                    </div>
                    <span class="ts-badge"><i class="bi bi-robot me-1"></i>Live</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="ts-kpi orange p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="label">Unique Humans ({{ $range === 'today' ? 'Today' : "Last $days days" }})</div>
                        <div class="value">{{ $data['unique_humans'] }}</div>
                        <div class="hint">Distinct visitor keys</div>
                    </div>
                    <span class="ts-badge"><i class="bi bi-fingerprint me-1"></i>Unique</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="ts-kpi pink p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="label">Unique Bots ({{ $range === 'today' ? 'Today' : "Last $days days" }})</div>
                        <div class="value">{{ $data['unique_bots'] }}</div>
                        <div class="hint">Distinct visitor keys</div>
                    </div>
                    <span class="ts-badge"><i class="bi bi-shield-check me-1"></i>Detected</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="ts-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold"><i class="bi bi-eye me-2"></i>Pageviews (Humans)</div>
                    <span class="ts-badge">Range: {{ $range === 'today' ? 'Today' : "Last $days days" }}</span>
                </div>
                <div class="display-6 fw-bold mb-0">{{ $data['pageviews_humans'] }}</div>
                <div class="ts-subtitle mt-1">Counts only non-bot requests</div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="ts-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold"><i class="bi bi-collection me-2"></i>Pageviews (All)</div>
                    <span class="ts-badge">Humans + Bots</span>
                </div>
                <div class="display-6 fw-bold mb-0">{{ $data['pageviews_all'] }}</div>
                <div class="ts-subtitle mt-1">Includes crawlers and automation</div>
            </div>
        </div>
    </div>

    {{-- Tables --}}
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="ts-card">
                <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08) !important;">
                    <div class="fw-semibold"><i class="bi bi-signpost-2 me-2"></i>Top Pages (Humans)</div>
                    <div class="ts-subtitle">Most visited paths by humans</div>
                </div>

                <div class="table-responsive">
                    <table class="table ts-table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Path</th>
                            <th class="text-end">Hits</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($data['top_pages_humans'] as $row)
                            <tr>
                                <td class="text-break">
                                    <span class="ts-badge me-2"><i class="bi bi-link-45deg"></i></span>
                                    {{ $row['path'] }}
                                </td>
                                <td class="text-end fw-semibold">{{ $row['hits'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center ts-empty">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    No human pageviews yet
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="ts-card mb-3">
                <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08) !important;">
                    <div class="fw-semibold"><i class="bi bi-robot me-2"></i>Top Bots</div>
                    <div class="ts-subtitle">Crawlers hitting your site</div>
                </div>

                <div class="table-responsive">
                    <table class="table ts-table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Bot</th>
                            <th class="text-end">Hits</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($data['top_bots'] as $row)
                            <tr>
                                <td class="text-break">
                                    <span class="ts-badge me-2"><i class="bi bi-cpu"></i></span>
                                    {{ $row['bot'] }}
                                </td>
                                <td class="text-end fw-semibold">{{ $row['hits'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center ts-empty">
                                    <i class="bi bi-shield fs-4 d-block mb-2"></i>
                                    No bots detected in this range
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ts-card">
                <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08) !important;">
                    <div class="fw-semibold"><i class="bi bi-arrow-90deg-left me-2"></i>Top Referrers (Humans)</div>
                    <div class="ts-subtitle">Where people are coming from</div>
                </div>

                <div class="table-responsive">
                    <table class="table ts-table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Referrer</th>
                            <th class="text-end">Hits</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($data['top_referrers'] as $row)
                            <tr>
                                <td class="text-break">
                                    <span class="ts-badge me-2"><i class="bi bi-globe2"></i></span>
                                    {{ $row['referrer'] }}
                                </td>
                                <td class="text-end fw-semibold">{{ $row['hits'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center ts-empty">
                                    <i class="bi bi-compass fs-4 d-block mb-2"></i>
                                    No referrers yet
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="ts-foot mt-3">
        <i class="bi bi-lock me-1"></i>
        Tip: set dashboard middleware in <code>config/traffic-sentinel.php</code> to <code>['web','auth']</code> in production.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const key = 'traffic_sentinel_theme';
        const btn = document.getElementById('tsThemeBtn');
        const html = document.documentElement;

        const saved = localStorage.getItem(key);
        if (saved === 'light' || saved === 'dark') {
            html.setAttribute('data-bs-theme', saved);
        } else {
            html.setAttribute('data-bs-theme', 'dark');
        }

        btn?.addEventListener('click', function () {
            const next = (html.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem(key, next);

            const icon = btn.querySelector('i');
            if (icon) icon.className = (next === 'dark') ? 'bi bi-moon-stars' : 'bi bi-sun';
        });
    })();
</script>
</body>
</html>
