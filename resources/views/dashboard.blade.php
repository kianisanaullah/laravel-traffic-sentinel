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

        // Keep host/range/app always in links
        $qHost  = request('host', $selectedHost ?? '');
        $qRange = request('range', $range ?? 'today');
        $qApp   = request('app', $selectedApp ?? '');

        // Common query array (always safe to pass)
        $qs = ['app' => $qApp, 'host' => $qHost, 'range' => $qRange];

        // Keep any other params (path/referrer/etc) when changing filters (not used here but safe)
        $sticky = request()->except(['host','app','range','page']);

        if (!function_exists('ts_human_number')) {
            function ts_human_number($n, int $decimals = 1): string {
                if ($n === null) return '0';
                $n = (float) $n;
                $abs = abs($n);

                if ($abs >= 1000000000) return rtrim(rtrim(number_format($n/1000000000, $decimals, '.', ''), '0'), '.') . 'B';
                if ($abs >= 1000000)    return rtrim(rtrim(number_format($n/1000000,    $decimals, '.', ''), '0'), '.') . 'M';

                if ($abs >= 1000) {
                    $k = $n / 1000;
                    $d = ($abs >= 100000) ? 0 : $decimals; // 100,000+ => 0 decimals
                    return rtrim(rtrim(number_format($k, $d, '.', ''), '0'), '.') . 'K';
                }

                return (string) ((int)$n == $n ? (int)$n : $n);
            }
        }

        // Route names for unique IPs (you mentioned these are your actual names)
        $rUniqueIpsHumans = 'traffic-sentinel.unique-ips.humans';
        $rUniqueIpsBots   = 'traffic-sentinel.unique-ips.bots';

        // Optional: if you later rename to traffic-sentinel.unique.ips.* keep compatibility
        if (\Illuminate\Support\Facades\Route::has('traffic-sentinel.unique.ips.humans')) $rUniqueIpsHumans = 'traffic-sentinel.unique.ips.humans';
        if (\Illuminate\Support\Facades\Route::has('traffic-sentinel.unique.ips.bots'))   $rUniqueIpsBots   = 'traffic-sentinel.unique.ips.bots';
    @endphp

    {{-- Favicons --}}
    <link rel="icon" href="{{ $faviconIco }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ $faviconSvg }}">
    <link rel="icon" type="image/png" href="{{ $faviconPng }}">
    <link rel="apple-touch-icon" href="{{ $faviconPng }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --ts-bg: #0b1220;
            --ts-card-border: rgba(255, 255, 255, .12);
            --ts-soft: rgba(255, 255, 255, .65);
            --ts-muted: rgba(255, 255, 255, .55);
            --ts-line: rgba(255, 255, 255, .12);
            --ts-radius: 16px;
        }

        body {
            background: radial-gradient(1200px 500px at 10% -10%, rgba(99, 102, 241, .35), transparent 60%),
            radial-gradient(900px 450px at 90% 0%, rgba(16, 185, 129, .25), transparent 55%),
            radial-gradient(900px 450px at 20% 100%, rgba(236, 72, 153, .20), transparent 55%),
            var(--ts-bg);
            color: #e8eefc;
        }

        .ts-shell {
            max-width: 100%;
        }

        .ts-header {
            border: 1px solid var(--ts-line);
            background: rgba(255, 255, 255, .04);
            border-radius: var(--ts-radius);
            backdrop-filter: blur(10px);
        }

        .ts-title {
            letter-spacing: .2px;
            font-weight: 800;
            line-height: 1.1;
            font-size: 1.35rem;
        }

        .ts-subtitle {
            color: var(--ts-muted);
            font-size: .95rem;
            line-height: 1.25;
        }

        .ts-card {
            border-radius: var(--ts-radius);
            border: 1px solid var(--ts-card-border);
            background: rgba(255, 255, 255, .06);
            box-shadow: 0 18px 40px rgba(0, 0, 0, .25);
            backdrop-filter: blur(10px);
        }

        .ts-kpi {
            border-radius: var(--ts-radius);
            border: 1px solid var(--ts-card-border);
            background: linear-gradient(180deg, rgba(255, 255, 255, .10), rgba(255, 255, 255, .05));
            box-shadow: 0 18px 40px rgba(0, 0, 0, .25);
            backdrop-filter: blur(10px);
            overflow: hidden;
            position: relative;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
            height: 100%;
            min-height: 118px;
        }

        /* Make the whole card a stable clickable target */
        .ts-kpi-link {
            display: block;
            height: 100%;
        }

        .ts-kpi-link:focus-visible {
            outline: 3px solid rgba(99, 102, 241, .35);
            outline-offset: 3px;
            border-radius: var(--ts-radius);
        }

        .ts-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 50px rgba(0, 0, 0, .30);
        }

        .ts-kpi::before {
            content: "";
            position: absolute;
            inset: -40px -40px auto auto;
            width: 160px;
            height: 160px;
            border-radius: 999px;
            opacity: .55;
            background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, .9), transparent 60%);
        }

        .ts-kpi.green::before {
            background: radial-gradient(circle at 30% 30%, rgba(16, 185, 129, .9), transparent 60%);
        }

        .ts-kpi.pink::before {
            background: radial-gradient(circle at 30% 30%, rgba(236, 72, 153, .9), transparent 60%);
        }

        .ts-kpi.orange::before {
            background: radial-gradient(circle at 30% 30%, rgba(245, 158, 11, .95), transparent 60%);
        }

        .ts-kpi .label {
            color: var(--ts-muted);
            font-size: .9rem;
        }

        .ts-kpi .value {
            font-size: 2.05rem;
            font-weight: 900;
            letter-spacing: .2px;
            line-height: 1;
        }

        .ts-kpi .hint {
            color: var(--ts-muted);
            font-size: .85rem;
        }

        a .ts-kpi {
            cursor: pointer;
        }

        a .ts-kpi:hover {
            border-color: rgba(99, 102, 241, .45);
        }

        .ts-kpi.green:hover {
            border-color: rgba(16, 185, 129, .45);
        }

        .ts-kpi.orange:hover {
            border-color: rgba(245, 158, 11, .45);
        }

        .ts-kpi.pink:hover {
            border-color: rgba(236, 72, 153, .45);
        }

        .ts-pill {
            border: 1px solid var(--ts-line);
            background: rgba(255, 255, 255, .04);
            color: var(--ts-soft);
            border-radius: 999px;
            padding: .35rem .6rem;
            font-size: .85rem;
            white-space: nowrap;
        }

        .ts-table thead th {
            color: rgba(255, 255, 255, .75);
            font-weight: 700;
            border-bottom: 1px solid var(--ts-line);
            background: rgba(255, 255, 255, .03);
        }

        .ts-table td, .ts-table th {
            border-color: rgba(255, 255, 255, .08) !important;
            vertical-align: middle;
            padding-top: .65rem;
            padding-bottom: .65rem;
        }

        .ts-table tbody tr:nth-child(odd) {
            background: rgba(255, 255, 255, .015);
        }

        .ts-table tbody tr:hover {
            background: rgba(255, 255, 255, .04);
        }

        .ts-badge {
            font-size: .78rem;
            border-radius: 999px;
            padding: .25rem .55rem;
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .85);
            white-space: nowrap;
        }

        .ts-empty {
            padding: 2.2rem 1rem;
            color: var(--ts-muted);
        }

        .ts-foot {
            color: rgba(255, 255, 255, .55);
            font-size: .85rem;
        }

        .ts-logo {
            filter: drop-shadow(0 6px 14px rgba(99, 102, 241, .35));
        }

        .ts-nav .nav-link {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .12);
            color: rgba(255, 255, 255, .75);
            padding: .35rem .75rem;
            font-size: .9rem;
            white-space: nowrap;
        }

        .ts-nav .nav-link:hover {
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .92);
        }

        .ts-nav .nav-link.active {
            background: rgba(99, 102, 241, .30);
            border-color: rgba(99, 102, 241, .45);
            color: #fff;
        }

        .ts-row-link {
            cursor: pointer;
        }

        /* Better filter bar behavior on small screens */
        .ts-filterbar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
            justify-content: flex-end;
        }

        .ts-filterbar .form-select {
            min-width: 140px;
        }

        @media (max-width: 575.98px) {
            .ts-filterbar {
                justify-content: stretch;
            }

            .ts-filterbar .form-select {
                flex: 1 1 auto;
                min-width: 0;
            }

            .ts-pill {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* Light theme overrides */
        [data-bs-theme="light"] body {
            background: #f6f7fb;
            color: #0f172a;
        }

        [data-bs-theme="light"] .ts-header,
        [data-bs-theme="light"] .ts-card,
        [data-bs-theme="light"] .ts-kpi {
            background: #fff;
            border-color: rgba(15, 23, 42, .08);
            box-shadow: 0 16px 35px rgba(15, 23, 42, .08);
        }

        [data-bs-theme="light"] .ts-subtitle,
        [data-bs-theme="light"] .ts-kpi .label,
        [data-bs-theme="light"] .ts-kpi .hint,
        [data-bs-theme="light"] .ts-foot {
            color: rgba(15, 23, 42, .6);
        }

        [data-bs-theme="light"] .ts-table thead th {
            color: rgba(15, 23, 42, .7);
            background: rgba(15, 23, 42, .03);
            border-bottom-color: rgba(15, 23, 42, .08);
        }

        [data-bs-theme="light"] .ts-table td, [data-bs-theme="light"] .ts-table th {
            border-color: rgba(15, 23, 42, .07) !important;
        }

        [data-bs-theme="light"] .ts-table tbody tr:nth-child(odd) {
            background: rgba(15, 23, 42, .015);
        }

        [data-bs-theme="light"] .ts-table tbody tr:hover {
            background: rgba(15, 23, 42, .03);
        }

        [data-bs-theme="light"] .ts-pill {
            border-color: rgba(15, 23, 42, .10);
            background: rgba(15, 23, 42, .03);
            color: rgba(15, 23, 42, .75);
        }

        [data-bs-theme="light"] .ts-badge {
            border-color: rgba(15, 23, 42, .12);
            background: rgba(15, 23, 42, .03);
            color: rgba(15, 23, 42, .75);
        }

        [data-bs-theme="light"] a .ts-kpi:hover {
            border-color: rgba(99, 102, 241, .35);
        }

        [data-bs-theme="light"] a .ts-kpi.green:hover {
            border-color: rgba(16, 185, 129, .35);
        }

        [data-bs-theme="light"] a .ts-kpi.orange:hover {
            border-color: rgba(245, 158, 11, .35);
        }

        [data-bs-theme="light"] a .ts-kpi.pink:hover {
            border-color: rgba(236, 72, 153, .35);
        }

        [data-bs-theme="light"] .ts-nav .nav-link {
            border-color: rgba(15, 23, 42, .10);
            color: rgba(15, 23, 42, .75);
        }

        [data-bs-theme="light"] .ts-nav .nav-link:hover {
            background: rgba(15, 23, 42, .03);
            color: rgba(15, 23, 42, .92);
        }

        [data-bs-theme="light"] .ts-nav .nav-link.active {
            background: rgba(99, 102, 241, .12);
            border-color: rgba(99, 102, 241, .25);
            color: rgba(15, 23, 42, .95);
        }
    </style>
</head>

<body class="py-4">
<div class="container-fluid ts-shell px-4 px-xxl-5">

    {{-- Header --}}
    <div class="ts-header p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">

            <div class="d-flex align-items-center gap-3">
                <img src="{{ $logoSrc }}" alt="Traffic Sentinel" height="44" class="ts-logo">

                <div>
                    <div class="ts-title h4 mb-1">Traffic Sentinel</div>
                    <div class="ts-subtitle">
                        Visitors + Bots monitoring

                        @if(!empty($qApp))
                            <span class="ms-2 ts-badge"><i class="bi bi-boxes me-1"></i>{{ $qApp }}</span>
                        @endif

                        @if(!empty($qHost))
                            <span class="ms-2 ts-badge"><i class="bi bi-globe2 me-1"></i>{{ $qHost }}</span>
                        @else
                            <span class="ms-2 ts-badge"><i class="bi bi-diagram-3 me-1"></i>All Hosts</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="ts-filterbar">
                <span class="ts-pill">
                    <i class="bi bi-clock me-1"></i>
                    Online window: {{ config('traffic-sentinel.online_minutes', 5) }} min
                </span>

                <form method="get" class="d-flex flex-wrap gap-2 align-items-center">

                    {{-- Preserve any other query params (path/referrer/etc) --}}
                    @foreach($sticky as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $vv)
                                <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach

                    {{-- App filter --}}
                    <select name="app" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" @selected(empty($qApp))>All apps</option>
                        @foreach(($apps ?? []) as $a)
                            <option value="{{ $a }}" @selected($qApp === $a)>{{ $a }}</option>
                        @endforeach
                    </select>

                    {{-- Host filter --}}
                    <select name="host" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" @selected(empty($qHost))>All hosts</option>
                        @foreach(($hosts ?? []) as $h)
                            <option value="{{ $h }}" @selected($qHost === $h)>{{ $h }}</option>
                        @endforeach
                    </select>

                    {{-- Range filter --}}
                    <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today" @selected($qRange === 'today')>Today</option>
                        <option value="7" @selected($qRange === '7')>Last 7 days</option>
                        <option value="30" @selected($qRange === '30')>Last 30 days</option>
                    </select>

                    <button type="button" class="btn btn-sm btn-outline-secondary" id="tsThemeBtn" title="Toggle theme">
                        <i class="bi bi-moon-stars"></i>
                    </button>

                    <noscript>
                        <button class="btn btn-sm btn-primary">Apply</button>
                    </noscript>
                </form>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-3">
            <ul class="nav nav-pills ts-nav gap-2">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('traffic-sentinel.dashboard')) active @endif"
                       href="{{ route('traffic-sentinel.dashboard', $qs) }}">
                        <i class="bi bi-speedometer2 me-1"></i>Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('traffic-sentinel.online.*')) active @endif"
                       href="{{ route('traffic-sentinel.online.humans', $qs) }}">
                        <i class="bi bi-activity me-1"></i>Online
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('traffic-sentinel.unique.*')) active @endif"
                       href="{{ route('traffic-sentinel.unique.humans', $qs) }}">
                        <i class="bi bi-fingerprint me-1"></i>Unique
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('traffic-sentinel.pageviews.*')) active @endif"
                       href="{{ route('traffic-sentinel.pageviews.humans', $qs) }}">
                        <i class="bi bi-eye me-1"></i>Pageviews
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('traffic-sentinel.pages') || request()->routeIs('traffic-sentinel.pages.*')) active @endif"
                       href="{{ route('traffic-sentinel.pages', $qs) }}">
                        <i class="bi bi-signpost-2 me-1"></i>Pages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('traffic-sentinel.referrers') || request()->routeIs('traffic-sentinel.referrers.*')) active @endif"
                       href="{{ route('traffic-sentinel.referrers', $qs) }}">
                        <i class="bi bi-arrow-90deg-left me-1"></i>Referrers
                    </a>
                </li>
            </ul>
        </div>

        {{-- Active filters chips --}}
        <div class="mt-3 d-flex flex-wrap gap-2">
            @if(!empty($qApp))
                <span class="ts-pill"><i class="bi bi-boxes me-1"></i>{{ $qApp }}</span>
            @endif
            @if(!empty($qHost))
                <span class="ts-pill"><i class="bi bi-globe2 me-1"></i>{{ $qHost }}</span>
            @endif
            <span class="ts-pill">
                <i class="bi bi-calendar3 me-1"></i>{{ $qRange === 'today' ? 'Today' : "Last $days days" }}
            </span>
        </div>
    </div>

    {{-- KPI grid --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-2">
            <a class="text-decoration-none text-reset ts-kpi-link"
               href="{{ route('traffic-sentinel.online.humans', $qs) }}">
                <div class="ts-kpi p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="label">Online Humans</div>
                            <div class="value">{{ ts_human_number($onlineHumans) }}</div>
                            <div class="hint">Last {{ config('traffic-sentinel.online_minutes', 5) }} min</div>
                        </div>
                        <span class="ts-badge"><i class="bi bi-person-check me-1"></i>Live</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
            <a class="text-decoration-none text-reset ts-kpi-link"
               href="{{ route('traffic-sentinel.online.bots', $qs) }}">
                <div class="ts-kpi green p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="label">Online Bots</div>
                            <div class="value">{{ ts_human_number($onlineBots) }}</div>
                            <div class="hint">Crawlers / tools</div>
                        </div>
                        <span class="ts-badge"><i class="bi bi-robot me-1"></i>Live</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
            <a class="text-decoration-none text-reset ts-kpi-link"
               href="{{ route('traffic-sentinel.unique.humans', $qs) }}">
                <div class="ts-kpi orange p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="label">Unique Humans ({{ $qRange === 'today' ? 'Today' : "Last $days days" }})
                            </div>
                            <div class="value">{{ ts_human_number($data['unique_humans'] ?? 0) }}</div>
                            <div class="hint">Distinct visitor keys</div>
                        </div>
                        <span class="ts-badge"><i class="bi bi-fingerprint me-1"></i>Unique</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
            <a class="text-decoration-none text-reset ts-kpi-link"
               href="{{ route('traffic-sentinel.unique.bots', $qs) }}">
                <div class="ts-kpi pink p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="label">Unique Bots ({{ $qRange === 'today' ? 'Today' : "Last $days days" }})
                            </div>
                            <div class="value">{{ ts_human_number($data['unique_bots'] ?? 0) }}</div>
                            <div class="hint">Distinct visitor keys</div>
                        </div>
                        <span class="ts-badge"><i class="bi bi-shield-check me-1"></i>Detected</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
            <a class="text-decoration-none text-reset ts-kpi-link" href="{{ route($rUniqueIpsHumans, $qs) }}">
                <div class="ts-kpi p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="label">Unique IPs (Humans)</div>
                            <div class="value">{{ ts_human_number($data['unique_ips_humans'] ?? 0) }}</div>
                            <div class="hint">{{ $qRange === 'today' ? 'Today' : "Last $days days" }}</div>
                        </div>
                        <span class="ts-badge"><i class="bi bi-geo-alt me-1"></i>IPs</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
            <a class="text-decoration-none text-reset ts-kpi-link" href="{{ route($rUniqueIpsBots, $qs) }}">
                <div class="ts-kpi green p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="label">Unique IPs (Bots)</div>
                            <div class="value">{{ ts_human_number($data['unique_ips_bots'] ?? 0) }}</div>
                            <div class="hint">{{ $qRange === 'today' ? 'Today' : "Last $days days" }}</div>
                        </div>
                        <span class="ts-badge"><i class="bi bi-geo me-1"></i>IPs</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <a class="text-decoration-none text-reset d-block"
               href="{{ route('traffic-sentinel.pageviews.humans', $qs) }}">
                <div class="ts-card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold"><i class="bi bi-eye me-2"></i>Pageviews (Humans)</div>
                        <span class="ts-badge">Range: {{ $qRange === 'today' ? 'Today' : "Last $days days" }}</span>
                    </div>
                    <div class="display-6 fw-bold mb-0">{{ ts_human_number($data['pageviews_humans'] ?? 0) }}</div>
                    <div class="ts-subtitle mt-1">Counts only non-bot requests</div>
                </div>
            </a>
        </div>

        <div class="col-12 col-lg-6">
            <a class="text-decoration-none text-reset d-block"
               href="{{ route('traffic-sentinel.pageviews.all', $qs) }}">
                <div class="ts-card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold"><i class="bi bi-collection me-2"></i>Pageviews (All)</div>
                        <span class="ts-badge">Humans + Bots</span>
                    </div>
                    <div class="display-6 fw-bold mb-0">{{ ts_human_number($data['pageviews_all'] ?? 0) }}</div>
                    <div class="ts-subtitle mt-1">Includes crawlers and automation</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Tables --}}
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="ts-card">
                <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,.08) !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <a class="text-decoration-none text-reset fw-semibold"
                               href="{{ route('traffic-sentinel.pages', $qs) }}">
                                <i class="bi bi-signpost-2 me-2"></i>Top Pages (Humans)
                            </a>
                            <div class="ts-subtitle">Most visited paths by humans</div>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('traffic-sentinel.pages', $qs) }}">
                            Focus <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
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
                        @forelse(($data['top_pages_humans'] ?? []) as $row)
                            @php
                                $to = route('traffic-sentinel.pages.path', array_merge($qs, ['path' => $row['path']]));
                            @endphp
                            <tr class="ts-row-link" onclick="window.location='{{ $to }}'">
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
                        @forelse(($data['top_bots'] ?? []) as $row)
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
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <a class="text-decoration-none text-reset fw-semibold"
                               href="{{ route('traffic-sentinel.referrers', $qs) }}">
                                <i class="bi bi-arrow-90deg-left me-2"></i>Top Referrers (Humans)
                            </a>
                            <div class="ts-subtitle">Where people are coming from</div>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="{{ route('traffic-sentinel.referrers', $qs) }}">
                            Focus <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
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
                        @forelse(($data['top_referrers'] ?? []) as $row)
                            @php
                                $to = route('traffic-sentinel.referrers.show', array_merge($qs, ['referrer' => $row['referrer']]));
                            @endphp
                            <tr class="ts-row-link" onclick="window.location='{{ $to }}'">
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
        Tip: set dashboard middleware in <code>config/traffic-sentinel.php</code> to <code>['web','auth']</code> in
        production.
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

        const setIcon = () => {
            const theme = html.getAttribute('data-bs-theme');
            const icon = btn?.querySelector('i');
            if (icon) icon.className = (theme === 'dark') ? 'bi bi-moon-stars' : 'bi bi-sun';
        };
        setIcon();

        btn?.addEventListener('click', function () {
            const next = (html.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem(key, next);
            setIcon();
        });
    })();
</script>
</body>
</html>
