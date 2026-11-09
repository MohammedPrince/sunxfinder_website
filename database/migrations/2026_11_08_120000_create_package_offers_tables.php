<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Package offers: a company builds a ready system (panels, inverter, batteries…) for loads
 * up to max_load_kw and pushes it to customers. A customer who accepts sends their devices
 * from the load calculator; that becomes a quote request for this company only, which the
 * company reviews and answers with a normal offer.
 *
 * No foreign keys on purpose: they behave differently on the MyISAM dev database and the
 * live one. Plain indexes only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tbl_package_offers')) {
            Schema::create('tbl_package_offers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('title_en', 150);
                $table->string('title_ar', 150)->nullable();
                $table->text('description_en')->nullable();
                $table->text('description_ar')->nullable();
                // The customer's peak load must be this or less.
                $table->decimal('max_load_kw', 8, 2);
                $table->string('currency', 3)->default('SDG');
                // Same line shape as tbl_invoices.items, plus type and product_id.
                $table->json('items');
                $table->decimal('subtotal', 15, 2);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('price', 15, 2);                  // subtotal - discount
                $table->timestamp('expires_at')->nullable();
                $table->string('status', 20)->default('active');  // active | closed
                $table->timestamp('sent_at')->nullable();         // first push; locks editing
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'status']);
            });
        }

        if (!Schema::hasTable('tbl_package_offer_responses')) {
            Schema::create('tbl_package_offer_responses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('package_offer_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('notification_id')->nullable();
                $table->string('status', 20);                     // accepted | declined
                $table->unsignedBigInteger('quote_id')->nullable(); // set when accepted
                $table->timestamp('responded_at');
                $table->timestamps();

                // One answer per customer and offer (a decline can later become an accept).
                $table->unique(['package_offer_id', 'customer_id']);
                $table->index('customer_id');
            });
        }

        Schema::table('tbl_quote_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_quote_requests', 'package_offer_id')) {
                $table->unsignedBigInteger('package_offer_id')->nullable()->index();
            }
        });

        Schema::table('tbl_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_notifications', 'package_offer_id')) {
                $table->unsignedBigInteger('package_offer_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        foreach (['tbl_quote_requests', 'tbl_notifications'] as $tableName) {
            if (Schema::hasColumn($tableName, 'package_offer_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['package_offer_id']);
                    $table->dropColumn('package_offer_id');
                });
            }
        }

        Schema::dropIfExists('tbl_package_offer_responses');
        Schema::dropIfExists('tbl_package_offers');
    }
};
