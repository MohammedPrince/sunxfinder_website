<?php

namespace App\Support;

use App\Helpers\Helper;
use App\Jobs\SendCompanyOfferDecisionEmail;
use App\Mail\CompanyOfferDecisionMail;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\Quote;
use App\Models\SolarCompany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The end of a project, after the company finished it (status handover_pending):
 *  - the customer confirms in the app            → confirm(..., 'customer')
 *  - nobody answers in Project::AUTO_CONFIRM_DAYS → autoConfirmDue() → confirm(..., 'auto')
 *  - the customer reports a problem              → reportProblem(): back to in_progress
 */
final class ProjectHandover
{
    public const HANDOVER_TITLE = 'customer handover';

    /**
     * Completes a handover_pending project and its request. Call inside a transaction with
     * the project locked. Emails the company once the transaction commits.
     */
    public static function confirm(Project $project, Quote $quote, string $by, ?int $customerId = null): void
    {
        $isAuto = $by === Project::CONFIRMED_BY_AUTO;
        $oldStatus = $project->status;

        $project->status = Project::COMPLETED;
        $project->progress_percent = 100;
        $now = Carbon::now();
        $project->completed_at = $now;
        $project->handover_confirmed_at = $now;
        $project->handover_confirmed_by = $isAuto ? Project::CONFIRMED_BY_AUTO : Project::CONFIRMED_BY_CUSTOMER;
        $project->save();

        SolarCompany::query()
            ->whereKey($project->company_id)
            ->update(['number_of_projects' => DB::raw('COALESCE(number_of_projects, 0) + 1')]);

        ProjectUpdate::query()->create([
            'project_id' => $project->id,
            'updated_by_type' => $isAuto ? 'system' : 'customer',
            'updated_by_id' => $isAuto ? null : $customerId,
            'old_status' => $oldStatus,
            'new_status' => Project::COMPLETED,
            'progress_percent' => 100,
            'message' => $isAuto
                ? 'The customer did not confirm or report a problem within ' . Project::AUTO_CONFIRM_DAYS . ' days, so the handover was confirmed automatically. The project is completed.'
                : 'The customer confirmed the handover in the app. The project is completed.',
            'visible_to_customer' => true,
        ]);

        $quote->status = 'completed';
        $quote->save();

        if ($project->company_quotation_id) {
            SendCompanyOfferDecisionEmail::dispatch(
                (int) $project->company_quotation_id,
                $isAuto ? CompanyOfferDecisionMail::AUTO_COMPLETED : CompanyOfferDecisionMail::COMPLETED
            );
        }
    }

    /**
     * Confirms every handover still waiting after Project::AUTO_CONFIRM_DAYS, then pushes
     * each customer. One transaction per project, so one failure does not stop the rest.
     *
     * @return array{confirmed: int, failed: int}
     */
    public static function autoConfirmDue(): array
    {
        $summary = ['confirmed' => 0, 'failed' => 0];

        $dueIds = Project::query()
            ->where('status', Project::HANDOVER_PENDING)
            ->whereNotNull('handover_requested_at')
            ->where('handover_requested_at', '<=', now()->subDays(Project::AUTO_CONFIRM_DAYS))
            ->orderBy('handover_requested_at')
            ->limit(500)
            ->pluck('id');

        foreach ($dueIds as $projectId) {
            try {
                $project = DB::transaction(function () use ($projectId) {
                    $project = Project::query()->whereKey($projectId)->lockForUpdate()->first();

                    // The customer may have answered since the list was read.
                    if (!$project || $project->status !== Project::HANDOVER_PENDING) {
                        return null;
                    }

                    $quote = Quote::query()->whereKey($project->quote_id)->lockForUpdate()->first();

                    if (!$quote) {
                        return null;
                    }

                    self::confirm($project, $quote, Project::CONFIRMED_BY_AUTO);

                    return $project;
                });

                if ($project) {
                    $summary['confirmed']++;
                    // After commit: tell the customer it is done and they can rate the company.
                    app(Helper::class)->notifyCustomerAboutProject($project, 'handover_auto_confirmed');
                    ActivityLogger::log('project_completed', 'Handover confirmed automatically after ' . Project::AUTO_CONFIRM_DAYS . ' days', $project, [
                        'quote_id' => (int) $project->quote_id,
                        'project_id' => (int) $project->id,
                    ], ['type' => 'system', 'id' => null, 'name' => 'Scheduler'], 'projects');
                }
            } catch (Throwable $e) {
                $summary['failed']++;
                report($e);
            }
        }

        return $summary;
    }

    /**
     * The customer did not accept the handover: the project goes back to in_progress
     * (handover step reopened, 99%) so the company can fix it and finish it again.
     * Call inside a transaction with the project locked; the project must be handover_pending.
     */
    public static function reportProblem(Project $project, int $customerId, string $message): void
    {
        ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->whereRaw('LOWER(TRIM(title)) = ?', [self::HANDOVER_TITLE])
            ->update(['status' => 'in_progress', 'completed_at' => null]);

        $project->status = Project::IN_PROGRESS;
        $project->progress_percent = 99;
        // Set again when the company finishes, which restarts the auto-confirm period.
        $project->handover_requested_at = null;
        $project->handover_problem_at = Carbon::now();
        $project->handover_problem_message = $message;
        $project->save();

        ProjectUpdate::query()->create([
            'project_id' => $project->id,
            'updated_by_type' => 'customer',
            'updated_by_id' => $customerId,
            'old_status' => Project::HANDOVER_PENDING,
            'new_status' => Project::IN_PROGRESS,
            'progress_percent' => 99,
            'message' => 'The customer reported a problem instead of confirming the handover: ' . $message,
            'visible_to_customer' => true,
        ]);

        if ($project->company_quotation_id) {
            SendCompanyOfferDecisionEmail::dispatch(
                (int) $project->company_quotation_id,
                CompanyOfferDecisionMail::HANDOVER_PROBLEM,
                ['problemMessage' => $message]
            );
        }
    }
}
