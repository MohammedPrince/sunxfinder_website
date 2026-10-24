<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Admin can stop the mobile app for one customer, with a message shown in the app.
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->enum('app_status', ['active', 'stopped'])->default('active')->after('last_login_at');
            $table->text('app_status_message_en')->nullable()->after('app_status');
            $table->text('app_status_message_ar')->nullable()->after('app_status_message_en');
            $table->timestamp('app_status_changed_at')->nullable()->after('app_status_message_ar');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->dropColumn(['app_status', 'app_status_message_en', 'app_status_message_ar', 'app_status_changed_at']);
        });
    }
};
