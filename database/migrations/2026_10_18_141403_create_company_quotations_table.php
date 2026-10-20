<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_company_quotations', function (Blueprint $table) {
            $table->id();

            // Original customer quote request
            $table->foreignId('quote_id')
                ->constrained('tbl_quote_requests')
                ->cascadeOnDelete();

            // Company submitting the offer
            $table->unsignedBigInteger('company_id')->index();

            // Price submitted by the company
            $table->decimal('price', 15, 2);
            $table->string('currency', 3)->default('SDG');

            // Optional explanation of the offer
            $table->text('message')->nullable();

            // Offer lifecycle
            $table->enum('status', [
                'submitted',
                'selected',
                'not_selected',
                'withdrawn',
            ])->default('submitted');

            $table->timestamps();
            $table->softDeletes();

            // One offer per company for each request
            $table->unique(
                ['quote_id', 'company_id'],
                'uq_company_quotation_request'
            );

            $table->index(['quote_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_company_quotations');
    }
};