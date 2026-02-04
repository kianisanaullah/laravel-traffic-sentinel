<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('traffic_pageviews', function (Blueprint $table) {
            if (! Schema::hasColumn('traffic_pageviews', 'host')) {
                $table->string('host', 191)->nullable()->after('traffic_session_id');

                // Good index for "per host within date range" queries
                $table->index(['host', 'viewed_at'], 'traffic_pageviews_host_viewed_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('traffic_pageviews', function (Blueprint $table) {
            if (Schema::hasColumn('traffic_pageviews', 'host')) {
                $table->dropIndex('traffic_pageviews_host_viewed_idx');
                $table->dropColumn('host');
            }
        });
    }
};
