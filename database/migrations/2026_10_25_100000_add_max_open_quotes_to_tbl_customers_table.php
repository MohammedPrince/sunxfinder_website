<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Admin can raise the open quote request limit for one customer (null = use the Settings default).
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_open_quotes')->nullable()->after('app_status_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->dropColumn('max_open_quotes');
        });
    }
};
