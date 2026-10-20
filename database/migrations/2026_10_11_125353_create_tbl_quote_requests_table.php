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
        Schema::create('tbl_quote_requests', function (Blueprint $table) {
            $table->id();

            // Customer contact information
            $table->string('customer_name', 150);
            $table->string('email')->nullable();
            $table->string('phone', 30);
            $table->string('city', 100)->nullable();

            // Requested solar solution
            $table->string('system_type', 100)->nullable();
            $table->text('requirements')->nullable();

            // New requests await admin review
            $table->string('status', 30)->default('new');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_quote_requests');
    }
};
