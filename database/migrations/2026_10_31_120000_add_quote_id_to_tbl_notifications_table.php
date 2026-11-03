<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_notifications', 'quote_id')) {
            Schema::table('tbl_notifications', function (Blueprint $table) {
                // The customer's request this notification is about, e.g. a new offer on it.
                $table->unsignedBigInteger('quote_id')->nullable()->after('company_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_notifications', 'quote_id')) {
            Schema::table('tbl_notifications', function (Blueprint $table) {
                $table->dropColumn('quote_id');
            });
        }
    }
};
