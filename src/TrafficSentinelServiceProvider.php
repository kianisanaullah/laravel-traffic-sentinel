<?php

namespace Kianisanaullah\TrafficSentinel;

use Illuminate\Support\ServiceProvider;
use Kianisanaullah\TrafficSentinel\Console\Commands\TrafficPruneCommand;
use Kianisanaullah\TrafficSentinel\Services\TrafficStats;
use Kianisanaullah\TrafficSentinel\Services\TrafficStatsRange;
use Kianisanaullah\TrafficSentinel\Services\TrafficTracker;

class TrafficSentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/traffic-sentinel.php', 'traffic-sentinel');

        $this->app->singleton(TrafficTracker::class, fn () => new TrafficTracker());
        $this->app->singleton(TrafficStats::class, fn () => new TrafficStats());
        $this->app->singleton(TrafficStatsRange::class, fn () => new TrafficStatsRange());
    }

    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../config/traffic-sentinel.php' => config_path('traffic-sentinel.php'),
        ], 'traffic-sentinel-config');

        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'traffic-sentinel-migrations');

        // Assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/traffic-sentinel'),
        ], 'traffic-sentinel-assets');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'traffic-sentinel');

        // Optional
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/traffic-sentinel'),
        ], 'traffic-sentinel-views');

        // Routes
        if (config('traffic-sentinel.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                TrafficPruneCommand::class,
            ]);
        }
    }
}
