<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Devices the customer picked in the app calculator (tbl_appliances ids).
        if (!Schema::hasColumn('tbl_quote_requests', 'appliances_ids')) {
            Schema::table('tbl_quote_requests', function (Blueprint $table) {
                $table->string('appliances_ids', 1000)->nullable()->after('daily_load');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_quote_requests', 'appliances_ids')) {
            Schema::table('tbl_quote_requests', function (Blueprint $table) {
                $table->dropColumn('appliances_ids');
            });
        }
    }
};
