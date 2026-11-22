<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The customer now confirms the handover in the app. When the company finishes, the project
     * becomes handover_pending (handover_requested_at); the customer's confirmation completes it
     * (handover_confirmed_at). status is a string column, so the new value needs no schema change.
     */
    public function up(): void
    {
        Schema::table('tbl_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_projects', 'handover_requested_at')) {
                $table->timestamp('handover_requested_at')->nullable()->after('started_at');
            }

            if (!Schema::hasColumn('tbl_projects', 'handover_confirmed_at')) {
                $table->timestamp('handover_confirmed_at')->nullable()->after('handover_requested_at');
            }
        });

        // Projects already completed count as confirmed, so customers are not asked about old jobs.
        DB::table('tbl_projects')
            ->where('status', 'completed')
            ->whereNull('handover_confirmed_at')
            ->update([
                'handover_requested_at' => DB::raw('COALESCE(handover_requested_at, completed_at, updated_at)'),
                'handover_confirmed_at' => DB::raw('COALESCE(completed_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        // Waiting projects go back to the old "in progress at 99%" state.
        DB::table('tbl_projects')
            ->where('status', 'handover_pending')
            ->update(['status' => 'in_progress', 'progress_percent' => 99]);

        Schema::table('tbl_projects', function (Blueprint $table) {
            $table->dropColumn(['handover_requested_at', 'handover_confirmed_at']);
        });
    }
};
