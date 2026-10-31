<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_solar_companies', 'can_notify_all_customers')) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                // Off: the company notifies only its own customers. Admin sets it in Settings.
                $table->boolean('can_notify_all_customers')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_solar_companies', 'can_notify_all_customers')) {
            Schema::table('tbl_solar_companies', function (Blueprint $table) {
                $table->dropColumn('can_notify_all_customers');
            });
        }
    }
};
