<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_otps', function (Blueprint $table) {
            // How the code was delivered: email now, sms once an SMS provider is connected.
            $table->string('channel', 10)->default('email')->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_otps', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
