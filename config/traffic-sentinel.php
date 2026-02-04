<?php

return [
    // Track these HTTP methods
    'methods' => ['GET', 'POST'],

    // Ignore route/path prefixes
    'ignore_prefixes' => [
        'telescope',
        'horizon',
        'nova',
        '_debugbar',
        'livewire',
    ],

    // Ignore file extensions
    'ignore_extensions' => [
        'css','js','map','jpg','jpeg','png','gif','webp','svg','ico','woff','woff2','ttf','eot','pdf','zip'
    ],

    // Consider online if last_seen_at within N minutes
    'online_minutes' => 5,

    // If true, ignore authenticated users
    'ignore_authenticated' => false,

    // Privacy: store full IP or hashed IP
    'ip' => [
        'store' => 'hashed', // 'full' or 'hashed'
        'hash_salt' => env('TRAFFIC_SENTINEL_IP_SALT', env('APP_KEY')),
    ],

    // Bot detection
    'bots' => [
        'enabled' => true,

        // IMPORTANT: we are tracking bots too
        'track_bots' => true,

        // keywords for basic detection
        'ua_keywords' => [
            'bot','crawl','spider','slurp','bingpreview','facebookexternalhit',
            'headless','lighthouse','pingdom','datadog','uptime','curl','wget',
            'python-requests','httpclient','go-http-client','scrapy','selenium'
        ],
    ],
    'cache' => [
        'enabled' => true,
        'prefix' => 'traffic_sentinel:',
    ],
    'dashboard' => [
        'enabled' => true,
        'path' => 'admin/traffic-sentinel',
        // Middleware stack for dashboard route
        // You can change in published config to ['web','auth'] or your admin middleware
        'middleware' => ['web','admin'],
    ],
    'exclude' => [

        /*
         |--------------------------------------------------------------------------
         | Excluded Paths
         |--------------------------------------------------------------------------
         | Paths (prefix match) that should NOT be tracked.
         | Examples:
         |  - admin
         |  - admin/traffic-sentinel
         |  - api
         |  - horizon
         |  - telescope
         */

        'paths' => [
            'admin/traffic-sentinel',
            'traffic-stats',
        ],

        /*
         |--------------------------------------------------------------------------
         | Excluded Route Names
         |--------------------------------------------------------------------------
         */

        'route_names' => [
            'traffic-sentinel.dashboard',
        ],

        /*
         |--------------------------------------------------------------------------
         | Excluded Hosts (optional)
         |--------------------------------------------------------------------------
         | Useful for ignoring internal tools, load balancers, etc.
         */

        'hosts' => [
            'localhost',
            '127.0.0.1',
            '976-tuna.com',
            'www.976-tuna.com',
        ],
        'ips' => [
            '127.0.0.1',
            '::1',
        ],
        'user_agents' => [
            'UptimeRobot',
            'Pingdom',
        ],

    ],
    'middleware' => [
        'auto_register' => false,
    ],
    'cookie' => [
        'name' => 'ts_vid',
        'minutes' => 60 * 24 * 365 * 2,
        'domain' => env('TS_COOKIE_DOMAIN', null),
        'secure' => env('TS_COOKIE_SECURE', null),
        'same_site' => 'Lax',
    ],

    'tracking' => [
        'include_host' => true,
    ],
];
