<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_app_sliders', 'company_id')) {
            Schema::table('tbl_app_sliders', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->index()->after('id');
                // Existing slides stay in the app only.
                $table->string('platform', 10)->default('app')->after('link_url');
                $table->boolean('is_sponsored')->default(false)->after('platform');
            });
        }

        if (!Schema::hasColumn('tbl_company_carousels', 'is_sponsored')) {
            Schema::table('tbl_company_carousels', function (Blueprint $table) {
                $table->boolean('is_sponsored')->default(false)->after('platform');
            });
        }

        if (!Schema::hasColumn('tbl_solar_companies', 'all_customers_monthly_limit')) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                // Null: no limit, as before this column existed.
                $table->unsignedInteger('all_customers_monthly_limit')->nullable()->after('can_notify_all_customers');
            });
        }

        if (!Schema::hasColumn('tbl_customers', 'sponsored_push_enabled')) {
            Schema::table('tbl_customers', function (Blueprint $table) {
                $table->boolean('sponsored_push_enabled')->default(true);
            });
        }

        if (!Schema::hasColumn('tbl_notifications', 'is_sponsored')) {
            Schema::table('tbl_notifications', function (Blueprint $table) {
                $table->boolean('is_sponsored')->default(false)->after('audience_city');
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'tbl_app_sliders' => ['company_id', 'platform', 'is_sponsored'],
            'tbl_company_carousels' => ['is_sponsored'],
            'tbl_solar_companies' => ['all_customers_monthly_limit'],
            'tbl_customers' => ['sponsored_push_enabled'],
            'tbl_notifications' => ['is_sponsored'],
        ];

        foreach ($columns as $tableName => $names) {
            $existing = array_values(array_filter($names, fn($name) => Schema::hasColumn($tableName, $name)));

            if ($existing) {
                Schema::table($tableName, fn(Blueprint $table) => $table->dropColumn($existing));
            }
        }
    }
};
