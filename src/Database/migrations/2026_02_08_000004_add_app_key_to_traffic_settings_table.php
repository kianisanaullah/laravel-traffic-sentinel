<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('traffic_settings', function (Blueprint $table) {
            $table->string('app_key')->after('id')->index();
            $table->unique(['app_key', 'key']);
        });
    }

    public function down()
    {
        Schema::table('traffic_settings', function (Blueprint $table) {
            $table->dropUnique(['app_key', 'key']);
            $table->dropColumn('app_key');
        });
    }
};
