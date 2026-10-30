<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->string('registration_source', 20)->default('unknown')->after('email');
            $table->char('preferred_language', 2)->default('en')->after('registration_source');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->dropColumn(['registration_source', 'preferred_language']);
        });
    }
};
