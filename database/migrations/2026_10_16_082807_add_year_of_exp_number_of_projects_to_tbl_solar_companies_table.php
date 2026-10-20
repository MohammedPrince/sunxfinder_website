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
            $table->integer('year_of_exp')
                ->nullable()
                ->after('ar_description');

            $table->integer('number_of_projects')
                ->nullable()
                ->after('year_of_exp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            //
        });
    }
};
