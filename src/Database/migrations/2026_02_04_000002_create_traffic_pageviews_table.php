<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traffic_pageviews', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('traffic_session_id')->index();

            $table->boolean('is_bot')->default(false)->index();
            $table->string('bot_name', 100)->nullable()->index();

            $table->string('method', 10)->index();
            $table->string('path', 500)->index();
            $table->string('full_url', 800)->nullable();
            $table->string('route_name', 191)->nullable()->index();

            $table->string('status_code', 10)->nullable();
            $table->integer('duration_ms')->nullable();

            $table->timestamp('viewed_at')->index();
            $table->timestamps();

            $table->foreign('traffic_session_id')
                ->references('id')
                ->on('traffic_sessions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_pageviews');
    }
};
