<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Guarded: older environments built from the legacy migrations may differ.
        if (Schema::hasColumn('tbl_quote_requests', 'service_needed')) {
            Schema::table('tbl_quote_requests', function (Blueprint $table) {
                $table->renameColumn('service_needed', 'quote_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_quote_requests', 'quote_name')) {
            Schema::table('tbl_quote_requests', function (Blueprint $table) {
                $table->renameColumn('quote_name', 'service_needed');
            });
        }
    }
};
