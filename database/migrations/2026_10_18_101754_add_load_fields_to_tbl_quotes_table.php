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
        Schema::table('tbl_quote_requests', function (Blueprint $table) {
            $table->text('peak_load')->nullable()->after('estimated_load');
            $table->text('daily_load')->nullable()->after('peak_load');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_quote_requests', function (Blueprint $table) {
            $table->dropColumn(['peak_load', 'daily_load']);
        });
    }
};
