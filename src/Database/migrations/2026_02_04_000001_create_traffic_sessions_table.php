<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traffic_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('session_id', 120)->index();      // Laravel session id
            $table->string('visitor_key', 120)->index();     // stable-ish unique key

            $table->boolean('is_bot')->default(false)->index();
            $table->string('bot_name', 100)->nullable()->index();

            $table->string('ip', 191)->nullable()->index();  // hashed/full (config)
            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 30)->nullable();   // bot/mobile/desktop/unknown

            $table->string('referrer', 500)->nullable();
            $table->string('landing_url', 500)->nullable();

            $table->timestamp('first_seen_at')->index();
            $table->timestamp('last_seen_at')->index();

            $table->unsignedBigInteger('user_id')->nullable()->index(); // if logged-in
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_sessions');
    }
};
