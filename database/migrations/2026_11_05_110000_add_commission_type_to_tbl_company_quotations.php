<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How an offer's SunXFinder commission is worked out:
 *  company: the company's rate (default; refreshed each time the company sends or edits the offer)
 *  percent: a percentage the admin set for this offer only
 *  fixed:   an amount the admin set for this offer only
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tbl_company_quotations', 'commission_type')) {
            Schema::table('tbl_company_quotations', function (Blueprint $table) {
                $table->string('commission_type', 10)->default('company')->after('currency');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_company_quotations', 'commission_type')) {
            Schema::table('tbl_company_quotations', fn(Blueprint $table) => $table->dropColumn('commission_type'));
        }
    }
};
