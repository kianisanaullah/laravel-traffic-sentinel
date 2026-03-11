<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficBotRulesTable extends Migration
{
    public function up()
    {
        Schema::create('traffic_bot_rules', function (Blueprint $table) {

            $table->id();

            $table->string('bot_name')->nullable()->index();
            $table->string('ip',45)->nullable()->index();
            $table->string('host')->nullable()->index();
            $table->string('app_key')->nullable()->index();

            $table->integer('limit_per_minute')->nullable();
            $table->integer('limit_per_hour')->nullable();
            $table->integer('limit_per_day')->nullable();

            $table->enum('action',['monitor','throttle','block'])->default('monitor');

            $table->boolean('enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('traffic_bot_rules');
    }
}
