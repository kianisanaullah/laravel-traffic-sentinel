<?php

use Illuminate\Support\Facades\Route;
use Kianisanaullah\TrafficSentinel\Http\Controllers\DashboardController;

Route::middleware(config('traffic-sentinel.dashboard.middleware', ['web']))
    ->prefix(config('traffic-sentinel.dashboard.path', 'admin/traffic-sentinel'))
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('traffic-sentinel.dashboard');
    });
