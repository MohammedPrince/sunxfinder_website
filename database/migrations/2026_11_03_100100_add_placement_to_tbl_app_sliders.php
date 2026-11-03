<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_app_sliders', 'placement')) {
            Schema::table('tbl_app_sliders', function (Blueprint $table) {
                // Existing slides stay where the app shows them today: under "Calculate my load".
                $table->string('placement', 30)->default('home_top')->index()->after('platform');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_app_sliders', 'placement')) {
            Schema::table('tbl_app_sliders', function (Blueprint $table) {
                $table->dropIndex(['placement']);
                $table->dropColumn('placement');
            });
        }
    }
};
