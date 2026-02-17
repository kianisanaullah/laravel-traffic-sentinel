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

    // Persist filters across all pages
    $qHost  = request('host', $selectedHost ?? '');
    $qRange = request('range', $range ?? 'today');
    $qApp   = request('app', $selectedApp ?? '');

    // Keep any other params (path/referrer/etc) when changing host/app/range
    $sticky = request()->except(['host','app','range','page']);
@endphp

<div class="ts-header p-3 p-md-4 mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">

        <div class="d-flex align-items-center gap-3">
            <img src="{{ $logoSrc }}" alt="Traffic Sentinel" height="44" class="ts-logo">

            <div>
                <div class="ts-title h4 mb-1">Traffic Sentinel</div>
                <div class="ts-subtitle">
                    Visitors + Bots monitoring

                    @if(!empty($qApp))
                        <span class="ms-2 ts-badge">
                            <i class="bi bi-boxes me-1"></i>{{ $qApp }}
                        </span>
                    @endif

                    @if(!empty($qHost))
                        <span class="ms-2 ts-badge">
                            <i class="bi bi-globe2 me-1"></i>{{ $qHost }}
                        </span>
                    @else
                        <span class="ms-2 ts-badge">
                            <i class="bi bi-diagram-3 me-1"></i>All Hosts
                        </span>
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

                {{-- App filter (optional if you have apps list) --}}
                @if(isset($apps))
                    <select name="app" class="form-select form-select-sm" onchange="this.form.submit()"
                            style="min-width: 170px;">
                        <option value="" @selected(empty($qApp))>All apps</option>
                        @foreach(($apps ?? []) as $a)
                            <option value="{{ $a }}" @selected($qApp === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                @else
                    {{-- keep app sticky even if this header is used in a page that didn't pass $apps --}}
                    @if(!empty($qApp))
                        <input type="hidden" name="app" value="{{ $qApp }}">
                    @endif
                @endif

                {{-- Host filter --}}
                <select name="host" class="form-select form-select-sm" onchange="this.form.submit()"
                        style="min-width: 210px;">
                    <option value="" @selected(empty($qHost))>All hosts</option>
                    @foreach(($hosts ?? []) as $h)
                        <option value="{{ $h }}" @selected($qHost === $h)>{{ $h }}</option>
                    @endforeach
                </select>

                {{-- Range filter --}}
                <select name="range" class="form-select form-select-sm" onchange="this.form.submit()"
                        style="min-width: 160px;">
                    <option value="today" @selected($qRange === 'today')>Today</option>
                    <option value="7" @selected($qRange === '7')>Last 7 days</option>
                    <option value="30" @selected($qRange === '30')>Last 30 days</option>
                </select>

                <button type="button" class="btn btn-sm btn-outline-secondary" id="tsThemeBtn" title="Toggle theme">
                    <i class="bi bi-moon-stars"></i>
                </button>

                <noscript><button class="btn btn-sm btn-primary">Apply</button></noscript>
            </form>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-3">
        <ul class="nav nav-pills ts-nav gap-2">
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('traffic-sentinel.dashboard')) active @endif"
                   href="{{ route('traffic-sentinel.dashboard', ['app' => $qApp, 'host' => $qHost, 'range' => $qRange]) }}">
                    <i class="bi bi-speedometer2 me-1"></i>Overview
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('traffic-sentinel.online.*')) active @endif"
                   href="{{ route('traffic-sentinel.online.humans', ['app' => $qApp, 'host' => $qHost, 'range' => $qRange]) }}">
                    <i class="bi bi-activity me-1"></i>Online
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('traffic-sentinel.unique.*') || request()->routeIs('traffic-sentinel.unique.ips.*')) active @endif"
                   href="{{ route('traffic-sentinel.unique.humans', ['app' => $qApp, 'host' => $qHost, 'range' => $qRange]) }}">
                    <i class="bi bi-fingerprint me-1"></i>Unique
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('traffic-sentinel.pageviews.*')) active @endif"
                   href="{{ route('traffic-sentinel.pageviews.humans', ['app' => $qApp, 'host' => $qHost, 'range' => $qRange]) }}">
                    <i class="bi bi-eye me-1"></i>Pageviews
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('traffic-sentinel.pages') || request()->routeIs('traffic-sentinel.pages.*')) active @endif"
                   href="{{ route('traffic-sentinel.pages', ['app' => $qApp, 'host' => $qHost, 'range' => $qRange]) }}">
                    <i class="bi bi-signpost-2 me-1"></i>Pages
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('traffic-sentinel.referrers') || request()->routeIs('traffic-sentinel.referrers.*')) active @endif"
                   href="{{ route('traffic-sentinel.referrers', ['app' => $qApp, 'host' => $qHost, 'range' => $qRange]) }}">
                    <i class="bi bi-arrow-90deg-left me-1"></i>Referrers
                </a>
            </li>
        </ul>
    </div>
</div>
