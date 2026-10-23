<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Admin-editable app settings, one row per key.
        Schema::create('tbl_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('value', 255)->nullable();
            $table->timestamps();
        });

        DB::table('tbl_settings')->insert([
            'key' => 'max_open_quotes',
            'value' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_settings');
    }
};
