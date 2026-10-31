<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A previous failed run may have left the table behind without its FKs.
        Schema::dropIfExists('tbl_invoices');

        $quotation = $this->parentKey('tbl_company_quotations');
        $quote = $this->parentKey('tbl_quote_requests');

        Schema::create('tbl_invoices', function (Blueprint $table) use ($quotation, $quote) {
            $table->id();

            // The offer this invoice belongs to. Removing an invoice soft-deletes
            // the row, and creating it again restores the same row.
            $this->addParentColumn($table, 'company_quotation_id', $quotation);
            $this->addParentColumn($table, 'quote_id', $quote);

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

            $table->unique('company_quotation_id');
            $table->index('quote_id');

            // Each company numbers its own invoices.
            $table->unique(
                ['company_id', 'invoice_number'],
                'uq_invoice_company_number'
            );

            // MyISAM parents cannot hold FKs; skip the constraint rather than fail.
            if ($quotation['can_add_foreign_key']) {
                $table->foreign('company_quotation_id')
                    ->references('id')
                    ->on('tbl_company_quotations')
                    ->cascadeOnDelete();
            }

            if ($quote['can_add_foreign_key']) {
                $table->foreign('quote_id')
                    ->references('id')
                    ->on('tbl_quote_requests')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_invoices');
    }

    /**
     * Read the real type and engine of a parent table's id (they differ on production).
     */
    private function parentKey(string $table): array
    {
        $parent = DB::selectOne(
            "SELECT c.COLUMN_TYPE AS column_type, t.ENGINE AS engine
             FROM information_schema.COLUMNS c
             JOIN information_schema.TABLES t
               ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND c.TABLE_NAME = ?
               AND c.COLUMN_NAME = 'id'",
            [$table]
        );

        $columnType = strtolower($parent->column_type ?? 'bigint unsigned');

        return [
            'is_big' => str_contains($columnType, 'bigint'),
            'is_unsigned' => str_contains($columnType, 'unsigned'),
            'can_add_foreign_key' => $parent && strtolower($parent->engine ?? '') === 'innodb',
        ];
    }

    private function addParentColumn(Blueprint $table, string $column, array $parent): void
    {
        if ($parent['is_big']) {
            $table->bigInteger($column, false, $parent['is_unsigned']);
        } else {
            $table->integer($column, false, $parent['is_unsigned']);
        }
    }
};
