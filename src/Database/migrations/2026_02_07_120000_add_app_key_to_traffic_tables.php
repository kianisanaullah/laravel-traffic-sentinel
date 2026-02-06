<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppKeyToTrafficTables extends Migration
{
    public function up(): void
    {
        // traffic_sessions
        Schema::table('traffic_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('traffic_sessions', 'app_key')) {
                $table
                    ->string('app_key', 50)
                    ->nullable()
                    ->index()
                    ->after('id'); // adjust if you prefer another position
            }
        });

        // traffic_pageviews
        Schema::table('traffic_pageviews', function (Blueprint $table) {
            if (!Schema::hasColumn('traffic_pageviews', 'app_key')) {
                $table
                    ->string('app_key', 50)
                    ->nullable()
                    ->index()
                    ->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('traffic_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('traffic_sessions', 'app_key')) {
                $table->dropIndex(['app_key']);
                $table->dropColumn('app_key');
            }
        });

        Schema::table('traffic_pageviews', function (Blueprint $table) {
            if (Schema::hasColumn('traffic_pageviews', 'app_key')) {
                $table->dropIndex(['app_key']);
                $table->dropColumn('app_key');
            }
        });
    }
}
