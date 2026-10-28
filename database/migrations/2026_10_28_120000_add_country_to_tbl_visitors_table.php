<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_visitors', function (Blueprint $table) {
            $table->char('country', 2)->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_visitors', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
