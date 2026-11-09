<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A load the customer calculated on the website before signing up. The app reads it
 * after login (GET /pending-load) so the customer does not add their appliances again,
 * then clears it. See App\Support\CalculatorLoad for its shape.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_customers', 'pending_load')) {
            Schema::table('tbl_customers', function (Blueprint $table) {
                $table->text('pending_load')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_customers', 'pending_load')) {
            Schema::table('tbl_customers', fn(Blueprint $table) => $table->dropColumn('pending_load'));
        }
    }
};
