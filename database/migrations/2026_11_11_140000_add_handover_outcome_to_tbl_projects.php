<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * How a handover ended, and the customer's "Report a problem":
     *  handover_confirmed_by     customer (in the app) or auto (no answer within Project::AUTO_CONFIRM_DAYS)
     *  handover_problem_at       last time the customer reported a problem instead of confirming
     *  handover_problem_message  what they wrote (the project went back to in_progress)
     */
    public function up(): void
    {
        Schema::table('tbl_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_projects', 'handover_confirmed_by')) {
                $table->string('handover_confirmed_by', 20)->nullable()->after('handover_confirmed_at');
            }

            if (!Schema::hasColumn('tbl_projects', 'handover_problem_at')) {
                $table->timestamp('handover_problem_at')->nullable()->after('handover_confirmed_by');
            }

            if (!Schema::hasColumn('tbl_projects', 'handover_problem_message')) {
                $table->text('handover_problem_message')->nullable()->after('handover_problem_at');
            }
        });

        // Finds waiting projects whose auto-confirm time has come (scheduled every hour).
        Schema::table('tbl_projects', function (Blueprint $table) {
            $table->index(['status', 'handover_requested_at'], 'tbl_projects_handover_due_index');
        });

        // Projects completed before this change were confirmed by people, not automatically.
        DB::table('tbl_projects')
            ->where('status', 'completed')
            ->whereNull('handover_confirmed_by')
            ->update(['handover_confirmed_by' => 'customer']);
    }

    public function down(): void
    {
        Schema::table('tbl_projects', function (Blueprint $table) {
            $table->dropIndex('tbl_projects_handover_due_index');
            $table->dropColumn(['handover_confirmed_by', 'handover_problem_at', 'handover_problem_message']);
        });
    }
};
