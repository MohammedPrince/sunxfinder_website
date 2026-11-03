<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tbl_ad_daily_stats')) {
            return;
        }

        // One row per ad, platform and day, so reports stay fast as traffic grows.
        Schema::create('tbl_ad_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->string('ad_type', 10);
            $table->unsignedBigInteger('ad_id');
            $table->string('platform', 10);
            $table->date('stat_date')->index();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['ad_type', 'ad_id', 'platform', 'stat_date'], 'ad_daily_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ad_daily_stats');
    }
};
