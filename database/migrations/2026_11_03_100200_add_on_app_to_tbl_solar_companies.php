<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * on_app: the company is shown in the customer app (/companies, /main-data, ...).
 * Set from the eye toggle on Admin → Company Carousel.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_solar_companies', 'on_app')) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                $table->boolean('on_app')->default(true)->index()->after('is_active');
            });
        }

        // Companies whose carousel entries were all switched off before this column existed.
        DB::table('tbl_solar_companies')
            ->whereIn('id', DB::table('tbl_company_carousels')->select('company_id'))
            ->whereNotIn('id', DB::table('tbl_company_carousels')->where('is_active', true)->select('company_id'))
            ->update(['on_app' => false]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_solar_companies', 'on_app')) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                $table->dropIndex(['on_app']);
                $table->dropColumn('on_app');
            });
        }
    }
};
