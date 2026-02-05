<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Traffic Sentinel' }}</title>

    @php
        $pubIco = public_path('vendor/traffic-sentinel/favicon.ico');
        $favicon = file_exists($pubIco) ? asset('vendor/traffic-sentinel/favicon.ico') : null;
    @endphp

    @if($favicon)
        <link rel="icon" href="{{ $favicon }}" sizes="any">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @include('traffic-sentinel::partials.styles')
</head>

<body class="py-4">

<div class="container-fluid px-3 px-lg-4">
    <div class="ts-shell-wide mx-auto">

        {{-- Big header (logo + filters) --}}
        @include('traffic-sentinel::partials.header')

        {{-- Page content --}}
        @yield('content')

        <div class="ts-foot mt-3">
            <i class="bi bi-lock me-1"></i>
            Tip: protect dashboard with middleware in
            <code>config/traffic-sentinel.php</code> in production.
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('traffic-sentinel::partials.scripts')

@stack('scripts')
</body>
</html>
