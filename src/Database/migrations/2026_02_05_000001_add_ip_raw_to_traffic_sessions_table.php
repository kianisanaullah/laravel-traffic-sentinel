<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpRawToTrafficSessionsTable extends Migration
{
    public function up()
    {
        Schema::table('traffic_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('traffic_sessions', 'ip_raw')) {
                $table->string('ip_raw', 45)->nullable()->after('ip');
            }
        });
    }

    public function down()
    {
        Schema::table('traffic_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('traffic_sessions', 'ip_raw')) {
                $table->dropColumn('ip_raw');
            }
        });
    }
}
