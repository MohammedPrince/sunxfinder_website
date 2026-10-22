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

            $table->foreign('created_by')
                ->references('id')
                ->on('tbl_admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_app_versions');
    }
};
