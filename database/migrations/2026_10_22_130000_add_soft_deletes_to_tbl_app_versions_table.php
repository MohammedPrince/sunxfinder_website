<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_app_versions', function (Blueprint $table) {
            $table->softDeletes();

            // A deleted row must not block re-adding the same platform;
            // one active row per platform is enforced in validation instead.
            $table->dropUnique(['platform']);
            $table->index(['platform', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_app_versions', function (Blueprint $table) {
            $table->dropIndex(['platform', 'deleted_at']);
            $table->unique('platform');
            $table->dropSoftDeletes();
        });
    }
};
