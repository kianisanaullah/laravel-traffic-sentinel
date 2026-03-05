<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_sessions_bots', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('app_key', 50)->nullable()->index();
            $table->string('session_id', 120)->index();
            $table->string('visitor_key', 120)->index();
            $table->string('host', 191)->nullable()->index();

            $table->string('bot_name', 100)->nullable()->index();

            $table->string('ip', 191)->nullable()->index();
            $table->string('ip_raw', 45)->nullable()->index();

            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('landing_url', 500)->nullable();

            $table->timestamp('first_seen_at')->index();
            $table->timestamp('last_seen_at')->index();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['host', 'last_seen_at'], 'ts_sess_b_host_last_seen');
            $table->index(['app_key', 'last_seen_at'], 'ts_sess_b_app_last_seen');
            $table->index(['bot_name', 'last_seen_at'], 'ts_sess_b_bot_last_seen');

            $table->index(['bot_name', 'first_seen_at'], 'ts_sess_b_bot_first_seen');
            $table->index(['ip', 'first_seen_at'], 'ts_sess_b_ip_first_seen');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $tbl = DB::getTablePrefix() . 'traffic_sessions_bots';
            DB::statement("CREATE INDEX `ts_sess_b_ref_seen` ON `{$tbl}` (`referrer`(191), `first_seen_at`)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_sessions_bots');
    }
};
