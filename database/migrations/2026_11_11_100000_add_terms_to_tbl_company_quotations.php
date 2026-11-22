<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Warranty and terms on a company's offer (warranty years per part, installation time,
     * payment terms, what is included…). Shape: App\Support\OfferTerms. NULL = none given,
     * so existing offers are unchanged.
     */
    public function up(): void
    {
        Schema::table('tbl_company_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_company_quotations', 'terms')) {
                $table->json('terms')->nullable()->after('ar_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_company_quotations', function (Blueprint $table) {
            $table->dropColumn('terms');
        });
    }
};
