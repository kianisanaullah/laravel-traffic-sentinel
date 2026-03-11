<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficBotRateLimitsTable extends Migration
{
    public function up()
    {
        Schema::create('traffic_bot_rate_limits', function (Blueprint $table) {

            $table->id();

            $table->string('ip',45)->index();
            $table->string('bot_name')->nullable()->index();

            $table->timestamp('minute_bucket')->index();

            $table->integer('requests')->default(0);

            $table->timestamps();

            $table->unique(['ip','minute_bucket'],'traffic_bot_rate_limits_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('traffic_bot_rate_limits');
    }
}
