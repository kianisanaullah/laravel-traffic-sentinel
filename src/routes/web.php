<?php

use Illuminate\Support\Facades\Route;
use Kianisanaullah\TrafficSentinel\Http\Controllers\AssetController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\BotController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\DashboardController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\ExploreController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\IpLogsController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\IpRuleController;
use Kianisanaullah\TrafficSentinel\Http\Controllers\CaptchaController;


Route::group([
    'prefix' => config('traffic-sentinel.dashboard.prefix', 'admin/traffic-sentinel'),
    'middleware' => config('traffic-sentinel.dashboard.middleware', ['web']),
], function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/', [DashboardController::class, 'index'])
        ->name('traffic-sentinel.dashboard');

    Route::get('/users', [DashboardController::class, 'users'])
        ->name('traffic-sentinel.users');

    Route::get('/users/{userId}', [DashboardController::class, 'userShow'])
        ->name('traffic-sentinel.users.show');

    Route::get('/sessions/{sessionId}/journey', [DashboardController::class, 'sessionJourney'])
        ->name('traffic-sentinel.session.journey');


    /*
    |--------------------------------------------------------------------------
    | Online
    |--------------------------------------------------------------------------
    */
    Route::prefix('online')->group(function () {
        Route::get('/humans', [ExploreController::class, 'onlineHumans'])
            ->name('traffic-sentinel.online.humans');

        Route::get('/bots', [ExploreController::class, 'onlineBots'])
            ->name('traffic-sentinel.online.bots');
    });


    /*
    |--------------------------------------------------------------------------
    | Unique
    |--------------------------------------------------------------------------
    */
    Route::prefix('unique')->group(function () {
        Route::get('/humans', [ExploreController::class, 'uniqueHumans'])
            ->name('traffic-sentinel.unique.humans');

        Route::get('/bots', [ExploreController::class, 'uniqueBots'])
            ->name('traffic-sentinel.unique.bots');
    });


    /*
    |--------------------------------------------------------------------------
    | Pageviews
    |--------------------------------------------------------------------------
    */
    Route::prefix('pageviews')->group(function () {
        Route::get('/humans', [ExploreController::class, 'pageviewsHumans'])
            ->name('traffic-sentinel.pageviews.humans');

        Route::get('/all', [ExploreController::class, 'pageviewsAll'])
            ->name('traffic-sentinel.pageviews.all');
    });


    /*
    |--------------------------------------------------------------------------
    | Pages + Referrers
    |--------------------------------------------------------------------------
    */
    Route::get('/pages', [ExploreController::class, 'pages'])
        ->name('traffic-sentinel.pages');

    Route::get('/pages/path', [ExploreController::class, 'pageviewsByPath'])
        ->name('traffic-sentinel.pages.path');

    Route::get('/referrers', [ExploreController::class, 'referrers'])
        ->name('traffic-sentinel.referrers');

    Route::get('/referrers/show', [ExploreController::class, 'sessionsByReferrer'])
        ->name('traffic-sentinel.referrers.show');

    Route::get('/ip/lookup', [ExploreController::class, 'ipLookup'])
        ->name('traffic-sentinel.ip.lookup');


    /*
    |--------------------------------------------------------------------------
    | Unique IPs
    |--------------------------------------------------------------------------
    */
    Route::prefix('unique-ips')->group(function () {
        Route::get('/humans', [ExploreController::class, 'uniqueIpsHumans'])
            ->name('traffic-sentinel.unique.ips.humans');

        Route::get('/bots', [ExploreController::class, 'uniqueIpsBots'])
            ->name('traffic-sentinel.unique.ips.bots');
    });


    /*
    |--------------------------------------------------------------------------
    | IP Logs
    |--------------------------------------------------------------------------
    */
    Route::prefix('ip-logs')->group(function () {
        Route::get('/humans', [IpLogsController::class, 'humans'])
            ->name('traffic-sentinel.ip-logs.humans');

        Route::get('/humans/data', [IpLogsController::class, 'humansData'])
            ->name('traffic-sentinel.ip-logs.humans.data');

        Route::get('/humans/focus/{ip}', [IpLogsController::class, 'humansFocus'])
            ->name('traffic-sentinel.ip-logs.humans.focus');

        Route::get('/bots', [IpLogsController::class, 'bots'])
            ->name('traffic-sentinel.ip-logs.bots');

        Route::get('/bots/data', [IpLogsController::class, 'botsData'])
            ->name('traffic-sentinel.ip-logs.bots.data');

        Route::get('/bots/focus/{ip}', [IpLogsController::class, 'botsFocus'])
            ->name('traffic-sentinel.ip-logs.bots.focus');
    });


    /*
    |--------------------------------------------------------------------------
    | Bot Rules
    |--------------------------------------------------------------------------
    */
    Route::prefix('bots')->group(function () {
        Route::get('/', [BotController::class, 'index'])
            ->name('traffic-sentinel.bots.index');

        Route::post('/block', [BotController::class, 'block'])
            ->name('traffic-sentinel.bots.block');

        Route::post('/throttle', [BotController::class, 'throttle'])
            ->name('traffic-sentinel.bots.throttle');

        Route::post('/monitor', [BotController::class, 'monitor'])
            ->name('traffic-sentinel.bots.monitor');
    });


    /*
    |--------------------------------------------------------------------------
    | IP Rules
    |--------------------------------------------------------------------------
    */
    Route::prefix('ips')->group(function () {
        Route::get('/', [IpRuleController::class, 'index'])
            ->name('traffic-sentinel.ips.index');

        Route::get('/focus/{ip}', [IpRuleController::class, 'show'])
            ->name('traffic-sentinel.ips.show');

        Route::post('/monitor', [IpRuleController::class, 'monitor'])
            ->name('traffic-sentinel.ips.monitor');

        Route::post('/block', [IpRuleController::class, 'block'])
            ->name('traffic-sentinel.ips.block');

        Route::post('/throttle', [IpRuleController::class, 'throttle'])
            ->name('traffic-sentinel.ips.throttle');
    });


});

Route::get('/captcha', [CaptchaController::class, 'show'])
    ->name('traffic-sentinel.captcha')
    ->withoutMiddleware(\Kianisanaullah\TrafficSentinel\Http\Middleware\TrackTraffic::class);

Route::post('/captcha/verify', [CaptchaController::class, 'verify'])
    ->name('traffic-sentinel.captcha.verify')
    ->withoutMiddleware(\Kianisanaullah\TrafficSentinel\Http\Middleware\TrackTraffic::class);


/*
|--------------------------------------------------------------------------
| Package Assets
|--------------------------------------------------------------------------
*/
Route::get('/traffic-sentinel/assets/{file}', [AssetController::class, 'show'])
    ->where('file', '.*')
    ->name('traffic-sentinel.asset');
