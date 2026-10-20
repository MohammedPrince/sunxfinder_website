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
        Schema::table('tbl_company_carousels', function (Blueprint $table) {
            $table->unique('company_id', 'tbl_company_carousels_company_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_company_carousels', function (Blueprint $table) {
            $table->dropUnique('tbl_company_carousels_company_id_unique');
        });
    }
};
