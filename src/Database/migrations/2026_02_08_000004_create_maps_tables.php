<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ts_map_h', function (Blueprint $t) {
            $t->unsignedBigInteger('old_id')->primary();
            $t->unsignedBigInteger('new_id')->index();
        });

        Schema::create('ts_map_b', function (Blueprint $t) {
            $t->unsignedBigInteger('old_id')->primary();
            $t->unsignedBigInteger('new_id')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ts_map_h');
        Schema::dropIfExists('ts_map_b');
    }
};
