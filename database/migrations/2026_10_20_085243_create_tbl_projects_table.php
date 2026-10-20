<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_projects', function (Blueprint $table) {
            $table->id();

            // Original customer request and accepted offer.
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('company_quotation_id')->unique();

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('company_id');

            // Project lifecycle.
            $table->string('status', 30)->default('accepted');
            $table->unsignedTinyInteger('progress_percent')->default(0);

            // Project dates.
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Relationships.
            $table->foreign('quote_id')
                ->references('id')
                ->on('tbl_quote_requests')
                ->restrictOnDelete();

            $table->foreign('company_quotation_id')
                ->references('id')
                ->on('tbl_company_quotations')
                ->restrictOnDelete();

            $table->foreign('customer_id')
                ->references('id')
                ->on('tbl_customers')
                ->restrictOnDelete();

            $table->foreign('company_id')
                ->references('id')
                ->on('tbl_solar_companies')
                ->restrictOnDelete();

            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_projects');
    }
};
