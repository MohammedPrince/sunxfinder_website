<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terms & Conditions acceptance. The app asks on every sign-in and sends the version
 * (the Terms page's "Last updated" date) the customer read.
 *
 * tbl_customer_terms_acceptances is an append-only log: one row per acceptance, never
 * updated. No foreign key on purpose: the history must outlive a deleted customer, and
 * foreign keys behave differently on the MyISAM dev database and the live one.
 *
 * tbl_customers.terms_accepted_version / terms_accepted_at: the latest acceptance, so
 * "has this customer accepted the current Terms?" is a simple column check.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tbl_customer_terms_acceptances')) {
            Schema::create('tbl_customer_terms_acceptances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->string('terms_version', 20);              // "2026-11-01"
                $table->char('lang', 2);                           // en | ar
                $table->string('source', 20)->default('login');    // login | register
                $table->string('platform', 10)->nullable();        // android | ios | web
                $table->string('app_version', 20)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('accepted_at');                  // server time
                $table->timestamps();

                $table->index(['customer_id', 'terms_version']);
                $table->index('accepted_at');
            });
        }

        Schema::table('tbl_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_customers', 'terms_accepted_version')) {
                $table->string('terms_accepted_version', 20)->nullable();
            }
            if (!Schema::hasColumn('tbl_customers', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_terms_acceptances');

        Schema::table('tbl_customers', function (Blueprint $table) {
            foreach (['terms_accepted_version', 'terms_accepted_at'] as $column) {
                if (Schema::hasColumn('tbl_customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
