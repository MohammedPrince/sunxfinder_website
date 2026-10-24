<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_customers', function (Blueprint $table) {
            // Set by the app login; shown in the admin customers list.
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        // Best guess for existing customers: when their current app token was created
        // (each login replaces the previous token).
        $latestTokens = DB::table('personal_access_tokens')
            ->where('tokenable_type', App\Models\Customer::class)
            ->selectRaw('tokenable_id, MAX(created_at) as logged_in_at')
            ->groupBy('tokenable_id')
            ->get();

        foreach ($latestTokens as $token) {
            DB::table('tbl_customers')
                ->where('id', $token->tokenable_id)
                ->whereNull('last_login_at')
                ->update(['last_login_at' => $token->logged_in_at]);
        }
    }

    public function down(): void
    {
        Schema::table('tbl_customers', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
