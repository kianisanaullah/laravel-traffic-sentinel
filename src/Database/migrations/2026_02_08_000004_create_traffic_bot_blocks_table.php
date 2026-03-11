<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficBotBlocksTable extends Migration
{
    public function up()
    {
        Schema::create('traffic_bot_blocks', function (Blueprint $table) {

            $table->id();

            $table->string('ip',45)->index();
            $table->string('bot_name')->nullable()->index();

            $table->string('host')->nullable();
            $table->string('app_key')->nullable();

            $table->string('reason')->nullable();

            $table->timestamp('blocked_until')->nullable();

            $table->boolean('manual')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('traffic_bot_blocks');
    }
}
