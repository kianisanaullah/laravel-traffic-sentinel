<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficBlockedAttemptsTable extends Migration
{
    public function up()
    {
        Schema::create('traffic_blocked_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 🔹 Core
            $table->string('ip', 64)->index();
            $table->string('bot_name')->nullable()->index();

            // 🔹 Request info
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('path')->nullable();
            $table->string('host')->nullable()->index();

            // 🔹 Reason + tracking
            $table->string('reason', 100)->index();
            $table->unsignedInteger('hits')->default(1);

            // 🔹 Time
            $table->timestamps();

            // 🔥 Useful index
            $table->index(['ip', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('traffic_blocked_attempts');
    }
}
