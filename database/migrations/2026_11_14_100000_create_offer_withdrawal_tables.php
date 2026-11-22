<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A company backing out of a deal the customer already accepted.
 *
 *  tbl_project_cancellations  every exit: an immediate withdrawal before work starts, or a
 *                             request that waits for an admin (after work started, or when the
 *                             company withdrew too often). Kept as history after the project is
 *                             soft-deleted, and counted against the company (App\Models\ProjectCancellation).
 *  tbl_offer_price_changes    the company asks to change the price of an accepted offer before work
 *                             starts; the customer accepts it or declines (the request reopens).
 *
 * No foreign keys: indexes only, so the migration behaves the same on MyISAM and InnoDB.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('tbl_project_cancellations');
        Schema::create('tbl_project_cancellations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('company_quotation_id')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            // Project status and progress when the company asked.
            $table->string('stage', 30);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('reason', 40);
            $table->text('note')->nullable();
            // withdrawn (done right away), requested (waits for admin), approved, rejected, cancelled (by the company)
            $table->string('status', 20);
            // Admin may waive it (e.g. the customer really was unreachable).
            $table->boolean('counts_against_company')->default(true);
            // How many other companies' offers were put back for the customer to choose from.
            $table->unsignedSmallInteger('reopened_offers')->default(0);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at'], 'tbl_project_cancellations_company_index');
            $table->index(['quote_id', 'status'], 'tbl_project_cancellations_quote_index');
            $table->index(['project_id', 'status'], 'tbl_project_cancellations_project_index');
            $table->index('status');
        });

        Schema::dropIfExists('tbl_offer_price_changes');
        Schema::create('tbl_offer_price_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_quotation_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('old_price', 15, 2);
            $table->decimal('new_price', 15, 2);
            $table->string('currency', 3)->default('SDG');
            $table->string('reason', 40);
            $table->text('note')->nullable();
            // pending, accepted, declined, cancelled (by the company)
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status'], 'tbl_offer_price_changes_project_index');
            $table->index(['quote_id', 'status'], 'tbl_offer_price_changes_quote_index');
            $table->index(['customer_id', 'status'], 'tbl_offer_price_changes_customer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_offer_price_changes');
        Schema::dropIfExists('tbl_project_cancellations');
    }
};
