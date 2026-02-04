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

        $this->app->singleton(TrafficTracker::class, function () {
            return new TrafficTracker();
        });
        $this->app->singleton(TrafficStats::class, function () {
            return new TrafficStats();
        });
        $this->app->singleton(TrafficStatsRange::class, function () {
            return new TrafficStatsRange();
        });

    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/traffic-sentinel.php' => config_path('traffic-sentinel.php'),
        ], 'traffic-sentinel-config');

        $this->publishes([
            __DIR__ . '/Database/migrations' => database_path('migrations'),
        ], 'traffic-sentinel-migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([
                TrafficPruneCommand::class,
            ]);
        }
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'traffic-sentinel');

        if (config('traffic-sentinel.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/traffic-sentinel'),
        ], 'traffic-sentinel-views');
    }
}
