<?php
return [

    'General' => [
        'traffic-sentinel.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.track_ajax' => ['type' => 'boolean'],
        'traffic-sentinel.track_livewire' => ['type' => 'boolean'],
        'traffic-sentinel.track_redirects' => ['type' => 'boolean'],
        'traffic-sentinel.track_errors' => ['type' => 'boolean'],
    ],

    'Tracking' => [
        'traffic-sentinel.tracking.methods' => ['type' => 'json'],
        'traffic-sentinel.tracking.ignore_authenticated' => ['type' => 'boolean'],
        'traffic-sentinel.tracking.online_minutes' => ['type' => 'number'],
        'traffic-sentinel.tracking.ignore_prefixes' => ['type' => 'json'],
        'traffic-sentinel.tracking.ignore_extensions' => ['type' => 'json'],
        'traffic-sentinel.tracking.include_host' => ['type' => 'boolean'],
    ],

    'IP Settings' => [
        'traffic-sentinel.ip.store' => ['type' => 'text'],
        'traffic-sentinel.privacy.store_raw_ip' => ['type' => 'boolean'],
    ],

    'IP Lookup' => [
        'traffic-sentinel.ip_lookup.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.ip_lookup.cache.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.ip_lookup.cache.ttl_hours' => ['type' => 'number'],
        'traffic-sentinel.ip_lookup.download.timeout' => ['type' => 'number'],
    ],

    'Bots' => [
        'traffic-sentinel.bots.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.bots.track_bots' => ['type' => 'boolean'],
        'traffic-sentinel.bots.ua_keywords' => ['type' => 'json'],
    ],

    'Middleware' => [
        'traffic-sentinel.middleware.auto_register' => ['type' => 'boolean'],
    ],

    'Exclusions' => [
        'traffic-sentinel.exclude.hosts' => ['type' => 'json'],
        'traffic-sentinel.exclude.paths' => ['type' => 'json'],
        'traffic-sentinel.exclude.ips' => ['type' => 'json'],
        'traffic-sentinel.exclude.user_agents' => ['type' => 'json'],
    ],

    'Cookie' => [
        'traffic-sentinel.cookie.name' => ['type' => 'text'],
        'traffic-sentinel.cookie.minutes' => ['type' => 'number'],
        'traffic-sentinel.cookie.same_site' => ['type' => 'text'],
    ],

    'Cache' => [
        'traffic-sentinel.cache.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.cache.prefix' => ['type' => 'text'],
    ],

    'UI' => [
        'traffic-sentinel.ui.ip_modal.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.ui.ip_modal.hydrate_flags' => ['type' => 'boolean'],
    ],

    'Database' => [
        'traffic-sentinel.database.connection' => ['type' => 'text'],
    ],

    'Captcha' => [
        'traffic-sentinel.captcha.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.captcha.challenge_minutes' => ['type' => 'number'],
        'traffic-sentinel.captcha.pass_minutes' => ['type' => 'number'],
        'traffic-sentinel.captcha.fail_limit' => ['type' => 'number'],
        'traffic-sentinel.captcha.block_hours' => ['type' => 'number'],
    ],
    'Alerts' => [
        'traffic-sentinel.alerts.enabled' => ['type' => 'boolean'],
        'traffic-sentinel.alerts.threshold' => ['type' => 'number'],
        'traffic-sentinel.alerts.window_seconds' => ['type' => 'number'],
        'traffic-sentinel.alerts.email' => ['type' => 'json'],
    ],

    'Captcha Keys' => [
        'traffic-sentinel.turnstile.site_key' => ['type' => 'text'],
        'traffic-sentinel.turnstile.secret_key' => ['type' => 'text'],
    ],
];
