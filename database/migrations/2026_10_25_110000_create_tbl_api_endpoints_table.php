<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // API servers the mobile app can use (Admin → Settings). Exactly one is active:
        // the app asks GET /api/v1/api-config which base URL to call and whether it is online.
        Schema::create('tbl_api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            // 191: a unique utf8mb4 index must fit in 767/1000 bytes on older MySQL/MyISAM.
            $table->string('base_url', 191)->unique();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_online')->default(true);
            $table->string('offline_message_en', 500)->nullable();
            $table->string('offline_message_ar', 500)->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('tbl_api_endpoints')->insert([
            ['name' => 'PRD', 'base_url' => 'https://sunxfinder.com/api/v1/customer', 'is_active' => true, 'is_online' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'DEV', 'base_url' => 'https://dev.sunxfinder.com/api/v1/customer', 'is_active' => false, 'is_online' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_api_endpoints');
    }
};
