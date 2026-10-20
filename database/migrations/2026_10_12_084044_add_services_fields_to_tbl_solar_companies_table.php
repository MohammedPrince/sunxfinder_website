<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                $table->text('services')->nullable()->after('city');
                $table->text('ar_services')->nullable()->after('services');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            $table->dropColumn(['services', 'ar_services']);
        });
    }
};
