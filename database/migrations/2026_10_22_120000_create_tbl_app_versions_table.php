<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_app_versions', function (Blueprint $table) {
            $table->id();

            // One row per platform: android, ios
            $table->string('platform', 20)->unique();

            $table->string('minimum_version', 20);
            $table->string('app_url', 500);

            // Block the app until the user updates to minimum_version.
            $table->boolean('force_update')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Plain index, not a foreign key: tbl_admins is MyISAM on some servers,
            // and MySQL cannot reference a MyISAM table (errno 150).
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_app_versions');
    }
};
