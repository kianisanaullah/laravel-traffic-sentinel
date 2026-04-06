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
use Kianisanaullah\TrafficSentinel\Models\TrafficSetting;


class TrafficSentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/traffic-sentinel.php', 'traffic-sentinel');
        $this->mergeConfigFrom(
            __DIR__ . '/../config/traffic-sentinel-settings-schema.php',
            'traffic-sentinel-settings-schema'
        );

        /*
        |--------------------------------------------------------------------------
        | Core Services
        |--------------------------------------------------------------------------
        */
        $this->app->singleton(TrafficTracker::class, fn () => new TrafficTracker());
        $this->app->singleton(TrafficStats::class, fn () => new TrafficStats());
        $this->app->singleton(TrafficStatsRange::class, fn () => new TrafficStatsRange());
        $this->app->singleton(RuntimeIpLookupService::class, fn () => new RuntimeIpLookupService());

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
        |Load DB Settings Override FIRST
        |--------------------------------------------------------------------------
        */
        try {
            $settings = cache()->remember('ts_settings_all', 3600, function () {
                return TrafficSetting::all();
            });

            foreach ($settings as $setting) {
                $value = $setting->value;

                if ($value !== null) {
                    config([$setting->key => $value]);
                }
            }
        } catch (\Throwable $e) {

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
        if (config('traffic-sentinel.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }


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
