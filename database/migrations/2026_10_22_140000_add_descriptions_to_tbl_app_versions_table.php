<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_app_versions', function (Blueprint $table) {
            // Update message shown to the user, in the app's language.
            $table->string('description_en', 500)->nullable()->after('force_update');
            $table->string('description_ar', 500)->nullable()->after('description_en');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_app_versions', function (Blueprint $table) {
            $table->dropColumn(['description_en', 'description_ar']);
        });
    }
};
