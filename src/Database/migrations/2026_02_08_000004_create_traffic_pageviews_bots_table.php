<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_pageviews_bots', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('app_key', 50)->nullable()->index();

            $table->unsignedBigInteger('traffic_session_id')->nullable()->index();
            $table->string('session_id', 120)->nullable()->index();
            $table->string('visitor_key', 120)->nullable()->index();

            $table->string('host', 191)->nullable()->index();

            $table->string('bot_name', 100)->nullable()->index();

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

            $table->index(['host', 'viewed_at'], 'ts_pv_b_host_viewed');
            $table->index(['app_key', 'viewed_at'], 'ts_pv_b_app_viewed');
            $table->index(['bot_name', 'viewed_at'], 'ts_pv_b_bot_viewed');

            $table->index(['ip', 'viewed_at'], 'ts_pv_b_ip_viewed');
            $table->index(['visitor_key', 'viewed_at'], 'ts_pv_b_visitor_viewed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_pageviews_bots');
    }
};
