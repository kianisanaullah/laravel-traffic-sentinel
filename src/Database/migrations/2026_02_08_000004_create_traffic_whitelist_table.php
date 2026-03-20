<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_whitelist_ips', function (Blueprint $table) {

            $table->id();

            $table->string('ip', 191)->index();

            $table->string('name')->nullable();      // Friendly name
            $table->string('description')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_whitelist_ips');
    }
};
