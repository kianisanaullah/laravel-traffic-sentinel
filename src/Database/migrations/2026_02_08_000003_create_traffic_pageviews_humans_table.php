<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_pageviews_humans', function (Blueprint $table) {
            // keep simple PK (no FK; avoids partition+FK limitations)
            $table->bigIncrements('id');

            $table->string('app_key', 50)->nullable()->index();

            // keep original link (optional) + denormalize for fast aggregation
            $table->unsignedBigInteger('traffic_session_id')->nullable()->index();
            $table->string('session_id', 120)->nullable()->index();
            $table->string('visitor_key', 120)->nullable()->index();

            $table->string('host', 191)->nullable()->index();

            // denormalized identity (so "top IPs" doesn't need join)
            $table->string('ip', 191)->nullable()->index();
            $table->string('ip_raw', 45)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('referrer', 500)->nullable();

            $table->string('method', 10);
            $table->string('path', 500);
            $table->string('full_url', 800)->nullable();
            $table->string('route_name', 191)->nullable();
            $table->string('status_code', 10)->nullable();
            $table->integer('duration_ms')->nullable();

            $table->timestamp('viewed_at')->index();
            $table->timestamps();

            // composites for dashboard (fast range scans + group by)
            $table->index(['host', 'viewed_at'], 'ts_pv_h_host_viewed');
            $table->index(['app_key', 'viewed_at'], 'ts_pv_h_app_viewed');
            $table->index(['path', 'viewed_at'], 'ts_pv_h_path_viewed');

            $table->index(['ip', 'viewed_at'], 'ts_pv_h_ip_viewed');
            $table->index(['visitor_key', 'viewed_at'], 'ts_pv_h_visitor_viewed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_pageviews_humans');
    }
};
