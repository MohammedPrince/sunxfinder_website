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
        Schema::create('tbl_customers', function (Blueprint $table) {
            $table->id();
            $table->string('username', 191)->unique();
            $table->string('password');
            $table->string('full_name', 200);
            $table->string('phone', 30)->unique();
            $table->string('email', 191)->nullable()->unique();

            $table->enum('gender', ['male', 'female'])
                ->nullable();

            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();

            $table->enum('status', ['active', 'pending', 'suspended', 'deleted'])->default('pending');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_customers');
    }
};
