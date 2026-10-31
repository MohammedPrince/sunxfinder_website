<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_invoices', function (Blueprint $table) {
            $table->id();

            // The offer this invoice belongs to. Removing an invoice soft-deletes
            // the row, and creating it again restores the same row.
            $table->foreignId('company_quotation_id')
                ->unique()
                ->constrained('tbl_company_quotations')
                ->cascadeOnDelete();

            $table->foreignId('quote_id')
                ->constrained('tbl_quote_requests')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            $table->string('invoice_number', 60);
            $table->date('invoice_date');
            $table->string('payment_terms', 150);
            $table->text('payment_instructions')->nullable();

            $table->string('currency', 3)->default('SDG');

            // Snapshots, so an issued invoice keeps the details it was created with.
            $table->json('company_details');
            $table->json('customer_details');
            $table->json('items');

            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('shipping', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2);

            $table->timestamps();
            $table->softDeletes();

            // Each company numbers its own invoices.
            $table->unique(
                ['company_id', 'invoice_number'],
                'uq_invoice_company_number'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_invoices');
    }
};
