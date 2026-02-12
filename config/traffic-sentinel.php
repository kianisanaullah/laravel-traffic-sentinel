<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tracking
    |--------------------------------------------------------------------------
    */
    'tracking' => [
        'app_key' => env('TRAFFIC_SENTINEL_APP_KEY', env('APP_NAME', 'app')),

        // Track these HTTP methods
        'methods' => ['GET', 'POST'],

        // If true, ignore authenticated users
        'ignore_authenticated' => false,

        // Consider "online" if last_seen_at is within N minutes
        'online_minutes' => 5,

        // Ignore route/path prefixes (prefix match)
        'ignore_prefixes' => [
            'telescope',
            'horizon',
            'nova',
            '_debugbar',
            'livewire',
        ],

        // Ignore file extensions
        'ignore_extensions' => [
            'css', 'js', 'map', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
            'woff', 'woff2', 'ttf', 'eot', 'pdf', 'zip',
        ],

        // If you want to store host in sessions/pageviews
        'include_host' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Storage & Privacy
    |--------------------------------------------------------------------------
    */
    'ip' => [
        // 'full' or 'hashed'
        'store' => env('TRAFFIC_SENTINEL_IP_STORE', 'full'),

        // Used only when store='hashed'
        'hash_salt' => env('TRAFFIC_SENTINEL_IP_SALT', env('APP_KEY')),
    ],
    'ip_lookup' => [
        'enabled' => env('TRAFFIC_SENTINEL_IP_LOOKUP', true),

        /*
        |--------------------------------------------------------------------------
        | Storage location (outside vendor/)
        |--------------------------------------------------------------------------
        | Keep datasets outside vendor so composer updates don't remove them.
        | This path is used by RuntimeIpLookupService + installer command.
        */
        'storage' => [
            'disk' => env('TRAFFIC_SENTINEL_IPDATA_DISK', 'local'),
            'path' => env('TRAFFIC_SENTINEL_IPDATA_PATH', 'traffic-sentinel/ipdata'),
            // full absolute path helper (avoid putting storage_path() into config cache)
            // We'll compute storage_path('app/'.path) in code.
        ],

        /*
        |--------------------------------------------------------------------------
        | Performance
        |--------------------------------------------------------------------------
        */
        'cache' => [
            'enabled' => true,
            'prefix' => 'traffic_sentinel:' . env('TRAFFIC_SENTINEL_APP_KEY', 'app') . ':',
            'ttl_hours' => 12,
        ],


        /*
        |--------------------------------------------------------------------------
        | Dataset download (GitHub Releases ZIP)
        |--------------------------------------------------------------------------
        */
        'download' => [
            'url' => env(
                'TRAFFIC_SENTINEL_IPDATA_URL',
                'https://github.com/kianisanaullah/traffic-sentinel-ipdata/releases/latest/download/ipdata.zip'
            ),
            'timeout' => (int)env('TRAFFIC_SENTINEL_IPDATA_TIMEOUT', 120),
            'user_agent' => 'TrafficSentinel/1.0 (+https://github.com/kianisanaullah/laravel-traffic-sentinel)',
        ],
    ],

    'privacy' => [
        // If ip.store='hashed', keep this false by default
        'store_raw_ip' => env('TRAFFIC_SENTINEL_STORE_RAW_IP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Detection
    |--------------------------------------------------------------------------
    */
    'bots' => [
        'enabled' => true,
        'track_bots' => true,

        'ua_keywords' => [
            'bot', 'crawl', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit',
            'headless', 'lighthouse', 'pingdom', 'datadog', 'uptime', 'curl', 'wget',
            'python-requests', 'httpclient', 'go-http-client', 'scrapy', 'selenium',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        // If true, auto push TrackTraffic to web group
        'auto_register' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclusions (Middleware-level)
    |--------------------------------------------------------------------------
    */
    'exclude' => [
        // Host exact matches
        'hosts' => [
            'localhost',
            '127.0.0.1',
        ],

        // Path prefix matches (without leading slash)
        'paths' => [
            'admin/traffic-sentinel',
            'traffic-stats',
        ],

        // IP exact matches
        'ips' => [
            '127.0.0.1',
            '::1',
        ],

        // UA contains matches
        'user_agents' => [
            'UptimeRobot',
            'Pingdom',
        ],

        // NOTE: route_names is not implemented in shouldExclude() currently.
        // Add support in middleware or remove this to avoid confusion.
        // 'route_names' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'enabled' => true,

        // IMPORTANT: use "prefix" because your routes/web.php expects it
        'prefix' => 'admin/traffic-sentinel',

        // Protect with your admin middleware
        'middleware' => ['web', 'admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie (Visitor Id)
    |--------------------------------------------------------------------------
    */
    'cookie' => [
        'name' => env('TRAFFIC_SENTINEL_COOKIE_NAME', 'ts_vid'),
        'minutes' => 60 * 24 * 365 * 2,
        'domain' => env('TS_COOKIE_DOMAIN', null),
        'secure' => env('TS_COOKIE_SECURE', null),
        'same_site' => 'Lax',
        'path' => '/',
        'http_only' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'prefix' => 'traffic_sentinel:',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'ip_modal' => [
            'enabled' => true,

            // JS uses __IP__ placeholder
            'endpoint' => '/admin/traffic-sentinel/ip/lookup?ip=__IP__',

            // hydrate flags inside tables automatically
            'hydrate_flags' => true,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */
    'database' => [
        /*
        |--------------------------------------------------------------------------
        | Connection
        |--------------------------------------------------------------------------
        | Database connection to use for traffic tables.
        | Default: mysql
        |
        | Examples:
        |   mysql
        |   reporting
        |   analytics
        */
        'connection' => env('TRAFFIC_SENTINEL_DB_CONNECTION', 'mysql'),
    ],
];
