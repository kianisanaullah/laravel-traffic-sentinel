<?php

use Illuminate\Support\Facades\Route;
use Kianisanaullah\TrafficSentinel\Http\Controllers\DashboardController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\ExploreController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController;

Route::group([
    'prefix' => config('traffic-sentinel.dashboard.prefix', 'admin/traffic-sentinel'),
    'middleware' => config('traffic-sentinel.dashboard.middleware', ['web']),
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('traffic-sentinel.dashboard');

    // Online
    Route::get('/online/humans', [ExploreController::class, 'onlineHumans'])->name('traffic-sentinel.online.humans');
    Route::get('/online/bots', [ExploreController::class, 'onlineBots'])->name('traffic-sentinel.online.bots');

    // Unique
    Route::get('/unique/humans', [ExploreController::class, 'uniqueHumans'])->name('traffic-sentinel.unique.humans');
    Route::get('/unique/bots', [ExploreController::class, 'uniqueBots'])->name('traffic-sentinel.unique.bots');

    // Pageviews
    Route::get('/pageviews/humans', [ExploreController::class, 'pageviewsHumans'])->name('traffic-sentinel.pageviews.humans');
    Route::get('/pageviews/all', [ExploreController::class, 'pageviewsAll'])->name('traffic-sentinel.pageviews.all');

    // Pages + Referrers
    Route::get('/pages', [ExploreController::class, 'pages'])->name('traffic-sentinel.pages');
    Route::get('/referrers', [ExploreController::class, 'referrers'])->name('traffic-sentinel.referrers');

    // Drilldowns
    Route::get('/pages/path', [ExploreController::class, 'pageviewsByPath'])->name('traffic-sentinel.pages.path');
    Route::get('/referrers/show', [ExploreController::class, 'sessionsByReferrer'])->name('traffic-sentinel.referrers.show');
    Route::get('/ip/lookup', [ExploreController::class, 'ipLookup'])
        ->name('traffic-sentinel.ip.lookup');

    // IPs
    Route::get('/unique-ips/humans', [ExploreController::class, 'uniqueIpsHumans'])
        ->name('traffic-sentinel.unique.ips.humans');

    Route::get('/unique-ips/bots', [ExploreController::class, 'uniqueIpsBots'])
        ->name('traffic-sentinel.unique.ips.bots');


    Route::get('/traffic-sentinel', [DashboardController::class, 'index'])->name('traffic-sentinel.dashboard');

    Route::get('/traffic-sentinel/users', [DashboardController::class, 'users'])->name('traffic-sentinel.users');
    Route::get('/traffic-sentinel/users/{userId}', [DashboardController::class, 'userShow'])->name('traffic-sentinel.users.show');

    Route::get('/traffic-sentinel/sessions/{sessionId}/journey', [DashboardController::class, 'sessionJourney'])
        ->name('traffic-sentinel.session.journey');

    // Humans IP logs
    Route::get('/ip-logs/humans', [\Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController::class, 'humans'])
        ->name('traffic-sentinel.ip-logs.humans');

    Route::get('/ip-logs/humans/data', [\Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController::class, 'humansData'])
        ->name('traffic-sentinel.ip-logs.humans.data');

    // Bots IP logs
    Route::get('/ip-logs/bots', [\Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController::class, 'bots'])
        ->name('traffic-sentinel.ip-logs.bots');

    Route::get('/ip-logs/bots/data', [\Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController::class, 'botsData'])
        ->name('traffic-sentinel.ip-logs.bots.data');

    // Optional focus routes (if you want them)
    Route::get('/ip-logs/humans/focus/{ip}', [\Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController::class, 'humansFocus'])
        ->name('traffic-sentinel.ip-logs.humans.focus');

    Route::get('/ip-logs/bots/focus/{ip}', [\Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController::class, 'botsFocus'])
        ->name('traffic-sentinel.ip-logs.bots.focus');});

Route::get(
    '/traffic-sentinel/assets/{file}',
    [\Kianisanaullah\TrafficSentinel\Http\Controllers\AssetController::class, 'show']
)->where('file', '.*')
    ->name('traffic-sentinel.asset');
