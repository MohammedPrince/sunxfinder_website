<?php

namespace App\Repositories\Company;

use App\Helpers\Helper;
use App\Models\CompanyQuotation;
use App\Models\OfferPriceChange;
use App\Models\Project;
use App\Models\ProjectCancellation;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\SolarCompany;
use App\Support\ActivityLogger;
use App\Support\OfferWithdrawal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ProjectRepository
{
    public function __construct(private Helper $helper)
    {
    }

    private function authenticatedCompanyId(): int
    {
        $company = Auth::guard('company')->user();

        abort_if(!$company, 403, 'Company authentication required.');

        return (int) $company->getAuthIdentifier();
    }

    public function getProjects(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->authenticatedCompanyId();

        return Project::query()
            ->with([
                'quote',
                'customer',
                'companyQuotation',
            ])
            ->where('company_id', $companyId)
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search)
                            ->orWhere('quote_id', (int) $search);
                    }

                    $q->orWhereHas('quote', function ($quoteQuery) use ($search) {
                        $quoteQuery->where('quote_name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            // tbl_customers has full_name/username, not name.
                            $customerQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function getProjectMetrics(): array
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Project::query()->where('company_id', $companyId);

        return [
            'total' => (clone $query)->count(),
            'accepted' => (clone $query)->where('status', 'accepted')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'handover_pending' => (clone $query)->where('status', Project::HANDOVER_PENDING)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];
    }

    public function getProjectById(int $id): ?Project
    {
        $companyId = $this->authenticatedCompanyId();

        return Project::query()
            ->with([
                'quote',
                'customer',
                'company',
                'companyQuotation',
                'milestones',
                'updates',
                'pendingPriceChange',
                'openCancellationRequest',
                'latestCancellation',
            ])
            ->where('company_id', $companyId)
            ->whereKey($id)
            ->first();
    }

    /** How many counted withdrawals the signed-in company has in the window, and whether it is flagged. */
    public function getWithdrawalStanding(): array
    {
        $count = ProjectCancellation::recentCountFor($this->authenticatedCompanyId());

        return [
            'count' => $count,
            'threshold' => ProjectCancellation::FLAG_THRESHOLD,
            'window_days' => ProjectCancellation::WINDOW_DAYS,
            'flagged' => $count >= ProjectCancellation::FLAG_THRESHOLD,
        ];
    }

    /**
     * The company backs out of an accepted deal.
     *  - not started (status accepted) and not flagged: done right away, the request reopens
     *  - started, finished, or the company withdrew too often lately: a request for an admin
     *
     * @return ProjectCancellation status "withdrawn" or "requested"
     */
    public function withdraw(int $projectId, array $data): ProjectCancellation
    {
        $companyId = $this->authenticatedCompanyId();
        $reopenedIds = [];

        $cancellation = DB::transaction(function () use ($projectId, $companyId, $data, &$reopenedIds) {
            $project = Project::query()
                ->where('company_id', $companyId)
                ->whereKey($projectId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanLeave($project);

            $note = trim((string) ($data['note'] ?? '')) ?: null;
            $immediate = $project->status === Project::ACCEPTED
                && !ProjectCancellation::isCompanyFlagged($companyId);

            // A request is read by an admin, who needs to know why.
            if (!$immediate && mb_strlen((string) $note) < 10) {
                throw ValidationException::withMessages(['note' => __('company/messages.deal.err_note_required')]);
            }

            $cancellation = new ProjectCancellation([
                'project_id' => $project->id,
                'quote_id' => $project->quote_id,
                'company_quotation_id' => $project->company_quotation_id,
                'company_id' => $companyId,
                'customer_id' => $project->customer_id,
                'stage' => $project->status,
                'progress_percent' => (int) $project->progress_percent,
                'reason' => $data['reason'],
                'note' => $note,
                'status' => $immediate ? ProjectCancellation::WITHDRAWN : ProjectCancellation::REQUESTED,
                'counts_against_company' => true,
            ]);
            $cancellation->save();

            if ($immediate) {
                $reopenedIds = OfferWithdrawal::endDeal($project, $cancellation, 'company', $companyId);
            }

            return $cancellation;
        });

        if ($cancellation->status === ProjectCancellation::WITHDRAWN) {
            OfferWithdrawal::notifyDealEnded($cancellation, $reopenedIds);
        } else {
            ActivityLogger::log('cancellation_requested', 'Company asked to cancel the job on request '
                . OfferWithdrawal::reference((int) $cancellation->quote_id) . ': ' . $cancellation->reasonText('en'), null, [
                'quote_id' => (int) $cancellation->quote_id,
                'project_id' => (int) $cancellation->project_id,
                'cancellation_id' => (int) $cancellation->id,
            ], null, 'projects');
        }

        return $cancellation;
    }

    /** The company takes back its cancellation request before an admin decides. */
    public function cancelWithdrawRequest(int $projectId): void
    {
        $companyId = $this->authenticatedCompanyId();

        $updated = ProjectCancellation::query()
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('status', ProjectCancellation::REQUESTED)
            ->update(['status' => ProjectCancellation::CANCELLED, 'decided_at' => now()]);

        if ($updated === 0) {
            throw ValidationException::withMessages(['reason' => __('company/messages.deal.err_no_request')]);
        }
    }

    /**
     * The company asks the customer for a new price before work starts. The customer accepts
     * (the offer gets the new price) or declines (the deal ends, without counting against it).
     */
    public function proposePriceChange(int $projectId, array $data): OfferPriceChange
    {
        $companyId = $this->authenticatedCompanyId();

        $change = DB::transaction(function () use ($projectId, $companyId, $data) {
            $project = Project::query()
                ->where('company_id', $companyId)
                ->whereKey($projectId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($project->status !== Project::ACCEPTED) {
                throw ValidationException::withMessages(['new_price' => __('company/messages.deal.err_price_started')]);
            }

            $this->assertCanLeave($project);

            if (OfferPriceChange::query()->where('project_id', $project->id)->where('status', OfferPriceChange::PENDING)->exists()) {
                throw ValidationException::withMessages(['new_price' => __('company/messages.deal.err_price_pending')]);
            }

            $offer = CompanyQuotation::query()
                ->whereKey($project->company_quotation_id)
                ->where('status', 'selected')
                ->lockForUpdate()
                ->first();

            if (!$offer) {
                throw ValidationException::withMessages(['new_price' => __('company/messages.deal.err_no_offer')]);
            }

            $newPrice = round((float) $data['new_price'], 2);

            if (abs($newPrice - (float) $offer->price) < 0.01) {
                throw ValidationException::withMessages(['new_price' => __('company/messages.deal.err_price_same')]);
            }

            return OfferPriceChange::query()->create([
                'company_quotation_id' => $offer->id,
                'project_id' => $project->id,
                'quote_id' => $project->quote_id,
                'company_id' => $companyId,
                'customer_id' => $project->customer_id,
                'old_price' => $offer->price,
                'new_price' => $newPrice,
                'currency' => $offer->currency,
                'reason' => $data['reason'],
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'status' => OfferPriceChange::PENDING,
            ]);
        });

        // After commit: ask the customer in the app.
        if ($change->customer_id) {
            $companyName = (string) SolarCompany::query()->whereKey($companyId)->value('name');
            $money = fn($value) => number_format((float) $value) . ' ' . $change->currency;
            $ref = OfferWithdrawal::reference((int) $change->quote_id);

            $this->helper->notifyCustomer((int) $change->customer_id, 'offer_price_changed', [
                'en' => [':company' => $companyName, ':ref' => $ref, ':old' => $money($change->old_price), ':new' => $money($change->new_price), ':reason' => lcfirst(OfferPriceChange::reasonLabel($change->reason, 'en'))],
                'ar' => [':company' => $companyName, ':ref' => $ref, ':old' => $money($change->old_price), ':new' => $money($change->new_price), ':reason' => OfferPriceChange::reasonLabel($change->reason, 'ar')],
            ], 'company', (int) $change->quote_id);
        }

        ActivityLogger::log('price_change_requested', 'Company asked to change the price on request ' . OfferWithdrawal::reference((int) $change->quote_id)
            . ' from ' . number_format((float) $change->old_price) . ' to ' . number_format((float) $change->new_price) . ' ' . $change->currency, null, [
            'quote_id' => (int) $change->quote_id,
            'project_id' => (int) $change->project_id,
            'price_change_id' => (int) $change->id,
        ], null, 'projects');

        return $change;
    }

    /** The company takes back its price change before the customer answers. */
    public function cancelPriceChange(int $projectId): void
    {
        $updated = OfferPriceChange::query()
            ->where('company_id', $this->authenticatedCompanyId())
            ->where('project_id', $projectId)
            ->where('status', OfferPriceChange::PENDING)
            ->update(['status' => OfferPriceChange::CANCELLED, 'responded_at' => now()]);

        if ($updated === 0) {
            throw ValidationException::withMessages(['new_price' => __('company/messages.deal.err_no_price_change')]);
        }
    }

    /** Leaving or changing the price is possible only on an open project without a waiting request. */
    private function assertCanLeave(Project $project): void
    {
        if ($project->status === Project::COMPLETED) {
            throw ValidationException::withMessages(['reason' => __('company/messages.projects.err_locked')]);
        }

        if (ProjectCancellation::query()->where('project_id', $project->id)->where('status', ProjectCancellation::REQUESTED)->exists()) {
            throw ValidationException::withMessages(['reason' => __('company/messages.deal.err_request_open')]);
        }
    }



    public function updateProgress(int $projectId, array $data): Project
    {
        $companyId = $this->authenticatedCompanyId();
        $oldStatus = null;

        $project = DB::transaction(function () use ($projectId, $companyId, $data, &$oldStatus) {
            $project = Project::query()
                ->where('company_id', $companyId)
                ->whereKey($projectId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $project->status;
            $requestedStatus = $data['status'];

            $fail = function (string $field, string $message): never {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field => $message,
                ]);
            };

            // A completed project is permanently locked.
            if ($oldStatus === Project::COMPLETED) {
                $fail(
                    'status',
                    __('company/messages.projects.err_locked')
                );
            }

            // Finished by the company: only the customer's confirmation in the app completes it.
            if ($oldStatus === Project::HANDOVER_PENDING) {
                $fail(
                    'status',
                    __('company/messages.projects.err_waiting_customer')
                );
            }

            // Installation cannot return to the Accepted stage.
            if ($oldStatus === 'in_progress' && $requestedStatus === 'accepted') {
                $fail(
                    'status',
                    __('company/messages.projects.err_started')
                );
            }

            if ($oldStatus === 'accepted' && $requestedStatus === 'completed') {
                $fail(
                    'status',
                    __('company/messages.projects.err_close_early')
                );
            }

            // The price is not agreed while the customer has a price change to answer.
            if (
                $oldStatus === 'accepted'
                && $requestedStatus !== 'accepted'
                && OfferPriceChange::query()->where('project_id', $project->id)->where('status', OfferPriceChange::PENDING)->exists()
            ) {
                $fail('status', __('company/messages.deal.err_price_pending_start'));
            }

            $milestones = ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $handover = $milestones->first(
                fn($milestone) =>
                    strtolower(trim($milestone->title)) === 'customer handover'
            );

            $installationMilestones = $milestones
                ->reject(
                    fn($milestone) =>
                        strtolower(trim($milestone->title)) === 'customer handover'
                )
                ->values();

            /*
             * Once handover has already been completed, no further
             * status or milestone changes are permitted.
             */
            if ($handover && $handover->status === 'completed') {
                $fail(
                    'status',
                    __('company/messages.projects.err_handover_done')
                );
            }

            /*
             * Accepted is a preparation stage. Milestone changes are
             * only permitted after installation starts.
             */
            if ($oldStatus === 'accepted' && $requestedStatus === 'accepted') {
                if (!empty($data['milestones'])) {
                    $fail(
                        'milestones',
                        __('company/messages.projects.err_start_first')
                    );
                }

                $progress = 0;
            } else {
                $submittedMilestones = $data['milestones'] ?? [];

                $allowedIds = $milestones
                    ->pluck('id')
                    ->map(fn($id) => (string) $id)
                    ->all();

                // Reject milestone IDs that do not belong to this project.
                foreach ($submittedMilestones as $milestoneId => $milestoneData) {
                    if (!in_array((string) $milestoneId, $allowedIds, true)) {
                        $fail(
                            'milestones',
                            __('company/messages.projects.err_foreign_milestone')
                        );
                    }
                }

                // Installation phases must be completed before handover.
                $submittedInstallationStatuses = [];

                foreach ($installationMilestones as $milestone) {
                    $submittedInstallationStatuses[$milestone->id] =
                        $submittedMilestones[$milestone->id]['status']
                        ?? $milestone->status;
                }

                $installationComplete = $installationMilestones->isNotEmpty()
                    && collect($submittedInstallationStatuses)->every(
                        fn($status) => $status === 'completed'
                    );

                $handoverSubmittedStatus = $handover
                    ? (
                        $submittedMilestones[$handover->id]['status']
                        ?? $handover->status
                    )
                    : null;

                if (
                    $handoverSubmittedStatus === 'completed'
                    && !$installationComplete
                ) {
                    $fail(
                        'milestones',
                        __('company/messages.projects.handover_locked')
                    );
                }

                if ($requestedStatus === 'completed') {
                    if (!$handover) {
                        $fail(
                            'milestones',
                            __('company/messages.projects.err_handover_missing')
                        );
                    }

                    if ($handoverSubmittedStatus !== 'completed') {
                        $fail(
                            'milestones',
                            __('company/messages.projects.err_confirm_handover')
                        );
                    }
                }

                /*
                 * Save the submitted milestone statuses.
                 * The transaction rolls back all changes if validation fails.
                 */
                foreach ($milestones as $milestone) {
                    if (!array_key_exists($milestone->id, $submittedMilestones)) {
                        continue;
                    }

                    $newMilestoneStatus =
                        $submittedMilestones[$milestone->id]['status'];

                    $isHandover = strtolower(trim($milestone->title))
                        === 'customer handover';

                    if (
                        $isHandover
                        && $newMilestoneStatus === 'completed'
                        && !$installationComplete
                    ) {
                        $fail(
                            'milestones',
                            __('company/messages.projects.err_all_phases')
                        );
                    }

                    $milestone->status = $newMilestoneStatus;

                    if ($newMilestoneStatus === 'in_progress') {
                        $milestone->started_at ??= now();
                    }

                    if ($newMilestoneStatus === 'completed') {
                        $milestone->started_at ??= now();
                        $milestone->completed_at ??= Carbon::now();
                    } else {
                        $milestone->completed_at = null;
                    }

                    if ($newMilestoneStatus === 'pending') {
                        $milestone->started_at = null;
                    }

                    $milestone->save();
                }

                // Refresh saved milestone states before calculating progress.
                $milestones = ProjectMilestone::query()
                    ->where('project_id', $project->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                $handover = $milestones->first(
                    fn($milestone) =>
                        strtolower(trim($milestone->title)) === 'customer handover'
                );

                $installationMilestones = $milestones
                    ->reject(
                        fn($milestone) =>
                            strtolower(trim($milestone->title)) === 'customer handover'
                    )
                    ->values();

                $installationComplete = $installationMilestones->isNotEmpty()
                    && $installationMilestones->every(
                        fn($milestone) => $milestone->status === 'completed'
                    );

                /*
                 * Completing the handover step finishes the company's work and locks the
                 * project, but does not complete it: it waits for the customer to confirm
                 * the handover in the app (CustomerRepository::completeQuote).
                 */
                if ($handover && $handover->status === 'completed') {
                    if (!$installationComplete) {
                        $fail(
                            'milestones',
                            __('company/messages.projects.err_milestones_handover')
                        );
                    }

                    $newStatus = Project::HANDOVER_PENDING;
                    $progress = 100;
                } elseif ($requestedStatus === 'completed') {
                    $fail(
                        'milestones',
                        __('company/messages.projects.err_complete_handover')
                    );
                } else {
                    $newStatus = $requestedStatus;

                    if ($newStatus === 'accepted') {
                        $progress = 0;
                    } elseif ($installationMilestones->isEmpty()) {
                        $progress = 0;
                    } else {
                        $completedCount = $installationMilestones
                            ->where('status', 'completed')
                            ->count();

                        $progress = $installationComplete
                            ? 99
                            : min(
                                99,
                                (int) floor(
                                    ($completedCount / $installationMilestones->count()) * 99
                                )
                            );
                    }
                }
            }

            $project->status = $newStatus;
            $project->progress_percent = $progress;

            if ($newStatus === 'in_progress' && !$project->started_at) {
                $project->started_at = now();
            }

            // The project, the request and the company's project count are completed
            // only when the customer confirms the handover (CustomerRepository::completeQuote).
            if ($newStatus === Project::HANDOVER_PENDING) {
                $project->handover_requested_at ??= Carbon::now();
                $project->progress_percent = 100;
            }

            $project->save();

            ProjectUpdate::query()->create([
                'project_id' => $project->id,
                'updated_by_type' => 'company',
                'updated_by_id' => $companyId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'progress_percent' => $project->progress_percent,
                'message' => $data['message'] ?? null,
                'visible_to_customer' => $data['visible_to_customer'] ?? true,
            ]);

            return $project->fresh([
                'quote',
                'customer',
                'companyQuotation',
                'milestones',
                'updates',
            ]);
        });

        // Notify the customer after commit, once per stage change.
        if ($oldStatus === 'accepted' && $project->status === 'in_progress') {
            // "Start Installation" pressed.
            $this->helper->notifyCustomerAboutProject($project, 'start_installation');
        } elseif ($oldStatus !== Project::HANDOVER_PENDING && $project->status === Project::HANDOVER_PENDING) {
            // The company finished: ask the customer to confirm the handover.
            $this->helper->notifyCustomerAboutProject($project, 'handover_requested');
        }

        return $project;
    }


}
