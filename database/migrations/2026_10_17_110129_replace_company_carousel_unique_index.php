<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_company_carousels', function (Blueprint $table) {
            // Remove uniqueness on company_id alone.
            $table->dropUnique(
                'tbl_company_carousels_company_id_unique'
            );

            // Allow the same company on different platforms,
            // but prevent duplicate entries for the same platform.
            $table->unique(
                ['company_id', 'platform'],
                'tbl_company_carousels_company_platform_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tbl_company_carousels', function (Blueprint $table) {
            $table->dropUnique(
                'tbl_company_carousels_company_platform_unique'
            );

            $table->unique(
                'company_id',
                'tbl_company_carousels_company_id_unique'
            );
        });
    }
};