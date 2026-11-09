<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SunXFinder commission: a percentage of each offer price, set per company by the admin.
 * Each offer keeps the rate and amount from when it was last submitted, so a later rate
 * change does not rewrite offers already sent. Shown to the admin and the company only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_solar_companies', 'commission_rate')) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->default(0)->after('is_active');
            });
        }

        if (!Schema::hasColumn('tbl_company_quotations', 'commission_rate')) {
            Schema::table('tbl_company_quotations', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->default(0)->after('currency');
                $table->decimal('commission_amount', 15, 2)->default(0)->after('commission_rate');
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'tbl_solar_companies' => ['commission_rate'],
            'tbl_company_quotations' => ['commission_rate', 'commission_amount'],
        ];

        foreach ($columns as $tableName => $names) {
            $existing = array_values(array_filter($names, fn($name) => Schema::hasColumn($tableName, $name)));

            if ($existing) {
                Schema::table($tableName, fn(Blueprint $table) => $table->dropColumn($existing));
            }
        }
    }
};
