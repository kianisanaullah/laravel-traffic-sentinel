<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('traffic_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('traffic_sessions', 'host')) {
                $table->string('host', 191)->nullable()->after('visitor_key');
                $table->index('host', 'traffic_sessions_host_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('traffic_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('traffic_sessions', 'host')) {
                $table->dropIndex('traffic_sessions_host_idx');
                $table->dropColumn('host');
            }
        });
    }
};
