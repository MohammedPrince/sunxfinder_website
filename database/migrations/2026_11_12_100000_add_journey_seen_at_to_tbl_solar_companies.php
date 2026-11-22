<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Company panel: the "how to use the system" journey opens once, on the company's first sign-in.
     * Empty = not shown yet, so existing companies also see it once on their next sign-in.
     */
    public function up(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_solar_companies', 'journey_seen_at')) {
                $table->timestamp('journey_seen_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            $table->dropColumn('journey_seen_at');
        });
    }
};
