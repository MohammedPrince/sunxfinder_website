<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_quotes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('company_id')->nullable();

            $table->string('service_needed', 100);
            $table->text('estimated_load')->nullable();
            $table->text('message')->nullable();

            // pending, accepted, rejected, completed, cancelled
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_quotes');
    }
};