<?php

namespace Kianisanaullah\TrafficSentinel;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Kianisanaullah\TrafficSentinel\Console\Commands\BackfillSplitTables;
use Kianisanaullah\TrafficSentinel\Console\Commands\IpDataInstallCommand;
use Kianisanaullah\TrafficSentinel\Console\Commands\TrafficPruneCommand;
use Kianisanaullah\TrafficSentinel\Http\Middleware\TrackTraffic;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotProtectionService;
use Kianisanaullah\TrafficSentinel\Services\RuntimeIpLookupService;
use Kianisanaullah\TrafficSentinel\Services\TrafficStats;
use Kianisanaullah\TrafficSentinel\Services\TrafficStatsRange;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;
use Kianisanaullah\TrafficSentinel\Services\Bots\BotRuleService;
use Kianisanaullah\TrafficSentinel\Services\CacheService;
use Kianisanaullah\TrafficSentinel\Models\TrafficSetting;
use Kianisanaullah\TrafficSentinel\Database\Seeders\TrafficSettingsSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Support\Facades\DB;


class TrafficSentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/traffic-sentinel.php', 'traffic-sentinel');
        $this->mergeConfigFrom(
            __DIR__ . '/../config/traffic-sentinel-settings-schema.php',
            'traffic-sentinel-settings-schema'
        );
        config([

            'cache.stores.traffic_sentinel_db' => [

                'driver' => 'traffic_sentinel_db',

            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Core Services
        |--------------------------------------------------------------------------
        */
        $this->app->singleton(TrafficTracker::class, fn () => new TrafficTracker());
        $this->app->singleton(TrafficStats::class, fn () => new TrafficStats());
        $this->app->singleton(TrafficStatsRange::class, fn () => new TrafficStatsRange());
        $this->app->singleton(RuntimeIpLookupService::class, function ($app) {
            return new RuntimeIpLookupService(
                $app->make(\Kianisanaullah\TrafficSentinel\Services\CacheService::class)
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Cache Service
        |--------------------------------------------------------------------------
        */
        $this->app->singleton(CacheService::class, function ($app) {

            return new CacheService();

        });
        /*
        |--------------------------------------------------------------------------
        | Protection / Rules
        |--------------------------------------------------------------------------
        */
        $this->app->singleton(BotProtectionService::class, fn () => new BotProtectionService());
        $this->app->singleton(BotRuleService::class, fn () => new BotRuleService());

        /*
        |--------------------------------------------------------------------------
        | Optional Bot Services
        |--------------------------------------------------------------------------
        */
        // $this->app->singleton(BotAnalyticsService::class, fn () => new BotAnalyticsService());
        // $this->app->singleton(BotTrafficService::class, fn () => new BotTrafficService());
    }


    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        |  App Key + Connection
        |--------------------------------------------------------------------------
        */
        $appKey = config('traffic-sentinel.tracking.app_key');
        $connection = config('traffic-sentinel.database.connection', 'mysql');
        Cache::extend('traffic_sentinel_db', function ($app) {

            $config = config('traffic-sentinel.cache');

            return Cache::repository(new DatabaseStore(

                DB::connection($config['connection'] ?? config('database.default')),

                $config['table'],

                $config['prefix']

            ));

        });
        /*
        |--------------------------------------------------------------------------
        | Auto Seed (SAFE + SCOPED + CONNECTION AWARE)
        |--------------------------------------------------------------------------
        */

        try {
            if (Schema::connection($connection)->hasTable('traffic_settings')) {

                // avoid running during artisan (important)
                if (!app()->runningInConsole()) {

                    $exists = TrafficSetting::where('app_key', $appKey)->exists();

                    if (!$exists) {
                        (new TrafficSettingsSeeder())->run();

                        cache()->forget("ts_settings_{$appKey}");
                    }
                }
            }
        } catch (\Throwable $e) {
            // silent fail (no crash on fresh install / migrations)
        }

        /*
        |--------------------------------------------------------------------------
        | Load DB Settings Override (SCOPED + CACHED)
        |--------------------------------------------------------------------------
        */
        try {
            if (Schema::connection($connection)->hasTable('traffic_settings')) {

                $settings = cache()->remember(
                    "ts_settings_{$appKey}",
                    3600,
                    function () use ($appKey) {
                        return TrafficSetting::where('app_key', $appKey)->get();
                    }
                );

                foreach ($settings as $setting) {
                    $value = $setting->value;
                    if ($value === null) continue;
                    if ($value === '1' || $value === 1) {
                        $value = true;
                    } elseif ($value === '0' || $value === 0) {
                        $value = false;
                    }
                    elseif (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    }
                    if (is_numeric($value)) {
                        $value = $value + 0;
                    }
                    config([$setting->key => $value]);
                }
            }
        } catch (\Throwable $e) {
            // silent fail
        }

        /*
        |--------------------------------------------------------------------------
        | UI / Pagination
        |--------------------------------------------------------------------------
        */
        Paginator::useBootstrapFive();

        $router = $this->app['router'];

        /*
        |--------------------------------------------------------------------------
        | Middleware auto-registration
        |--------------------------------------------------------------------------
        */
        $router->aliasMiddleware('traffic.sentinel', TrackTraffic::class);

        if (config('traffic-sentinel.middleware.auto_register', true)) {
            $router->pushMiddlewareToGroup('web', TrackTraffic::class);
        }

        /*
        |--------------------------------------------------------------------------
        | Views
        |--------------------------------------------------------------------------
        */
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'traffic-sentinel');

        /*
        |--------------------------------------------------------------------------
        | Routes
        |--------------------------------------------------------------------------
        */
//        if (config('traffic-sentinel.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
//        }

        /*
        |--------------------------------------------------------------------------
        | Publishable Resources
        |--------------------------------------------------------------------------
        */
        $this->publishes([
            __DIR__ . '/../config/traffic-sentinel.php' => config_path('traffic-sentinel.php'),
        ], 'traffic-sentinel-config');

        $this->publishes([
            __DIR__ . '/Database/migrations' => database_path('migrations'),
        ], 'traffic-sentinel-migrations');

        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/traffic-sentinel'),
        ], 'traffic-sentinel-assets');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/traffic-sentinel'),
        ], 'traffic-sentinel-views');

        /*
        |--------------------------------------------------------------------------
        | Console Commands
        |--------------------------------------------------------------------------
        */
        if ($this->app->runningInConsole()) {
            $this->commands([
                TrafficPruneCommand::class,
                IpDataInstallCommand::class,
                BackfillSplitTables::class,
            ]);
        }
    }
}
