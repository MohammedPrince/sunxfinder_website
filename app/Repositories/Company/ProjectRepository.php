<?php

namespace App\Repositories\Company;

use App\Helpers\Helper;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


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
            ])
            ->where('company_id', $companyId)
            ->whereKey($id)
            ->first();
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
            if ($oldStatus === 'completed') {
                $fail(
                    'status',
                    'This project is completed and locked. No further changes are allowed.'
                );
            }

            // Installation cannot return to the Accepted stage.
            if ($oldStatus === 'in_progress' && $requestedStatus === 'accepted') {
                $fail(
                    'status',
                    'Installation has already started. The project cannot return to Accepted.'
                );
            }

            if ($oldStatus === 'accepted' && $requestedStatus === 'completed') {
                $fail(
                    'status',
                    'Start the installation and complete all required phases before closing the project.'
                );
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
                    'Customer handover has been confirmed. This is irreversible, and the project is locked.'
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
                        'Start the installation before updating milestones.'
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
                            'One or more milestones do not belong to this project.'
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
                        'Complete every installation phase before confirming customer handover.'
                    );
                }

                if ($requestedStatus === 'completed') {
                    if (!$handover) {
                        $fail(
                            'milestones',
                            'The customer handover milestone is missing. Add it before completing the project.'
                        );
                    }

                    if ($handoverSubmittedStatus !== 'completed') {
                        $fail(
                            'milestones',
                            'Confirm customer handover before completing the project.'
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
                            'Complete all installation phases before confirming customer handover.'
                        );
                    }

                    $milestone->status = $newMilestoneStatus;

                    if ($newMilestoneStatus === 'in_progress') {
                        $milestone->started_at ??= now();
                    }

                    if ($newMilestoneStatus === 'completed') {
                        $milestone->started_at ??= now();
                        $milestone->completed_at ??= now();
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
                 * Completing handover automatically completes and locks
                 * the project in the same transaction.
                 */
                if ($handover && $handover->status === 'completed') {
                    if (!$installationComplete) {
                        $fail(
                            'milestones',
                            'All installation milestones must be completed before handover.'
                        );
                    }

                    $newStatus = 'completed';
                    $progress = 100;
                } elseif ($requestedStatus === 'completed') {
                    $fail(
                        'milestones',
                        'Complete customer handover before closing the project.'
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

            if ($newStatus === 'completed') {
                $project->completed_at ??= now();
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
        } elseif ($oldStatus !== 'completed' && $project->status === 'completed') {
            // Customer handover confirmed: the project is completed and locked.
            $this->helper->notifyCustomerAboutProject($project, 'project_completed');
        }

        return $project;
    }


}
