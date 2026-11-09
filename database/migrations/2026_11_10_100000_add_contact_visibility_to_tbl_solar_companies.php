<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Admin → Companies → Edit: show or hide the company's email and phone in the app.
     * Both start on, so nothing changes for existing companies.
     */
    public function up(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_solar_companies', 'show_email_in_app')) {
                $table->boolean('show_email_in_app')->default(true)->after('phone');
            }

            if (!Schema::hasColumn('tbl_solar_companies', 'show_phone_in_app')) {
                $table->boolean('show_phone_in_app')->default(true)->after('show_email_in_app');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            $table->dropColumn(['show_email_in_app', 'show_phone_in_app']);
        });
    }
};
