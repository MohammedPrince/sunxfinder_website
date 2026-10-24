<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_otps', function (Blueprint $table) {
            $table->id();

            // Account type the code belongs to: admin, company, customer
            $table->string('type', 20);
            $table->string('email', 191);

            // The 6-digit code is stored hashed, never in plain text.
            $table->string('code_hash');

            // What the code is for, so the same table can serve other OTP flows later.
            $table->string('purpose', 30)->default('password_reset');

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['type', 'email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_otps');
    }
};
