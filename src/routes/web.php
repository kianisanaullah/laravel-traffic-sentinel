<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::group([
    'prefix' => config('traffic-sentinel.dashboard.prefix', 'admin/traffic-sentinel'),
    'middleware' => config('traffic-sentinel.dashboard.middleware', ['web']),
], function () {
    Route::get('/', [\Kianisanaullah\TrafficSentinel\Http\Controllers\DashboardController::class, 'index'])
        ->name('traffic-sentinel.dashboard');
});


Route::get('/traffic-sentinel/assets/{file}', function (string $file) {
    $base = realpath(__DIR__ . '/../resources/assets');
    $path = realpath($base . DIRECTORY_SEPARATOR . $file);

    // Security: prevent path traversal
    if (! $path || ! str_starts_with($path, $base) || ! is_file($path)) {
        abort(404);
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    $mime = match ($ext) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'ico' => 'image/x-icon',
        default => 'application/octet-stream',
    };

    return Response::file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=604800', // 7 days
    ]);
})->where('file', '^[a-zA-Z0-9._-]+$')->name('traffic-sentinel.asset');
