<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_solar_companies', function (Blueprint $table) {
            $table->id();
            // Company profile
            $table->string('name', 200);
            $table->string('slug')->unique();
            $table->string('username')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('city', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
         
            // Company authentication
            $table->string('password')->nullable();
            $table->rememberToken();

            // Verification and account access
            $table->string('status', 30)->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['is_active', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_solar_companies');
    }
};
