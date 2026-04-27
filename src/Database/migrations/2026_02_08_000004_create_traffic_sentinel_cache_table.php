<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficSentinelCacheTable extends Migration
{
    public function up()
    {
        Schema::create('traffic_sentinel_cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value');
            $table->unsignedBigInteger('expiration')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('traffic_sentinel_cache');
    }
}
