<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('traffic-sentinel.dashboard.prefix', 'admin/traffic-sentinel'),
    'middleware' => config('traffic-sentinel.dashboard.middleware', ['web']),
], function () {
    Route::get('/', [\Kianisanaullah\TrafficSentinel\Http\Controllers\DashboardController::class, 'index'])
        ->name('traffic-sentinel.dashboard');
});
