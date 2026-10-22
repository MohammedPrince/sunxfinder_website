<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Kept here (not read from the model) so this migration never changes later.
    private const STATUSES = ['pending', 'approved', 'rejected', 'deleted'];

    public function up(): void
    {
        // Any value outside the enum would make the ALTER fail, so it goes back to review.
        DB::table('tbl_solar_companies')
            ->where(fn($q) => $q->whereNull('status')->orWhereNotIn('status', self::STATUSES))
            ->update(['status' => 'pending']);

        // Companies removed before this change are marked as deleted.
        DB::table('tbl_solar_companies')
            ->whereNotNull('deleted_at')
            ->update(['status' => 'deleted']);

        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            $table->enum('status', self::STATUSES)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_solar_companies', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });
    }
};
