<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_admins', function (Blueprint $table) {
            // Used for password recovery; existing admins start without one.
            $table->string('email', 191)->nullable()->unique()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_admins', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }
};
