<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Traffic Sentinel Dashboard')</title>

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

        // Keep host/range always in links
        $qHost  = request('host', $selectedHost ?? '');
        $qRange = request('range', $range ?? 'today');
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

    @yield('head')
</head>
