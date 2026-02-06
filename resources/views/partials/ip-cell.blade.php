@php
    $mode = config('traffic-sentinel.ip.store');
    $ipShown = $ip ?? null;
    $isRealIp = $ipShown && filter_var($ipShown, FILTER_VALIDATE_IP);
@endphp

@if($mode === 'hashed')
    <span class="text-muted small">Hashed</span>
@elseif(!$isRealIp)
    <span class="text-muted small">—</span>
@else
    <a href="#"
       class="ts-ip-link text-decoration-none d-inline-flex align-items-center gap-1"
       data-ts-ip="{{ $ipShown }}"
       title="Click to view IP details">
        <span class="ts-ip-flag-inline" data-ts-flag="{{ $ipShown }}">🌐</span>
        <code class="small">{{ $ipShown }}</code>
    </a>
@endif
