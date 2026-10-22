
@extends('company.layouts.app')

@section('title', 'Project Progress')
@section('breadcrumb', 'Active Projects')
@section('eyebrow', 'PROJECT MANAGEMENT')
@section('page-title', 'Project Progress')
@section('page-description', 'Run this installation step by step. Every update you save is shown to the customer.')

@section('page-actions')
    <div class="quote-page-actions">
        <a href="{{ route('company.projects.index') }}" class="btn btn-light">
            &larr; Back to Projects
        </a>
    </div>
@endsection

@section('content')
    @php
        $quote = $project->quote;
        $customer = $project->customer;
        $offer = $project->companyQuotation;

        $customerName = $customer?->full_name
            ?: ($customer?->username ?: 'Customer #' . $project->customer_id);

        $reference = '#Q-' . str_pad(
            (string) $project->quote_id,
            5,
            '0',
            STR_PAD_LEFT
        );

        $statusLabels = [
            'accepted' => 'Accepted',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
        ];

        $statusClasses = [
            'accepted' => 'pill-orange',
            'in_progress' => 'pill-blue',
            'completed' => 'pill-green',
        ];

        $stages = ['accepted', 'in_progress', 'completed'];

        $currentStage = array_search($project->status, $stages, true);
        $currentStage = $currentStage === false ? 0 : $currentStage;

        $milestones = $project->milestones
            ->sortBy('sort_order')
            ->values();

        $handover = $milestones->first(
            fn ($milestone) =>
                strtolower(trim($milestone->title)) === 'customer handover'
        );

        $installationMilestones = $milestones->reject(
            fn ($milestone) =>
                strtolower(trim($milestone->title)) === 'customer handover'
        )->values();

        $installationTotal = $installationMilestones->count();

        $completedInstallationMilestones = $installationMilestones
            ->where('status', 'completed')
            ->count();

        $totalMilestones = $milestones->count();

        $completedMilestones = $milestones
            ->where('status', 'completed')
            ->count();

        $installationComplete = $installationTotal > 0
            && $completedInstallationMilestones === $installationTotal;

        $handoverComplete = $handover?->status === 'completed';

        $isAccepted = $project->status === 'accepted';
        $isInProgress = $project->status === 'in_progress';
        $isCompleted = $project->status === 'completed';

        $currentMilestone = $installationMilestones->first(
            fn ($milestone) => $milestone->status !== 'completed'
        );

        $canUpdateHandover = $isInProgress
            && $installationComplete
            && $handover
            && !$handoverComplete;

        /*
         * Progress rules:
         * Accepted     = 0%
         * In progress  = installation milestone completion ratio, capped at 99%
         * Completed    = 100%
         *
         * Customer handover is excluded from the installation percentage.
         */
        if ($isCompleted) {
            $progress = 100;
            $progressLabel = 'Project completed';
        } elseif ($isAccepted) {
            $progress = 0;
            $progressLabel = 'Waiting to start';
        } elseif ($installationComplete) {
            $progress = 99;
            $progressLabel = $handoverComplete
                ? 'Handover recorded'
                : 'Installation complete — handover pending';
        } elseif ($installationTotal > 0) {
            $progress = (int) floor(
                ($completedInstallationMilestones / $installationTotal) * 99
            );

            $progressLabel = 'Installation phases';
        } else {
            $progress = 0;
            $progressLabel = 'Installation phases pending';
        }

        $milestoneLabels = [
            'pending' => 'Not started',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
        ];

        $milestoneClasses = [
            'pending' => 'pill-blue',
            'in_progress' => 'pill-yellow',
            'completed' => 'pill-green',
        ];
    @endphp

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Unable to update the project.</strong>
            <ul style="margin:8px 0 0;padding-left:20px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('partials.page-guide', [
        'key' => 'company-project-details',
        'title' => 'How to run this project',
        'intro' => 'The customer accepted your offer. Follow the three stages below; the right-hand panel always shows your next action.',
        'steps' => [
            '<strong>Start installation</strong> when your team is ready to begin work.',
            '<strong>Complete each milestone</strong> in order, adding a short message for the customer. Progress is calculated automatically.',
            '<strong>Confirm customer handover</strong> once the system is installed and explained. This closes the project at 100% and cannot be undone.',
        ],
        'note' => 'Use the customer contact details below to arrange site visits and installation dates.',
    ])

    {{-- Project summary --}}
    <section class="panel">
        <div class="quote-detail-grid">
            <div class="quote-detail">
                <span>Request reference</span>
                <strong>
                    <a href="{{ route('company.quote-requests.show', $project->quote_id) }}">{{ $reference }}</a>
                </strong>
                <small>Project #{{ $project->id }}</small>
            </div>

            <div class="quote-detail">
                <span>Customer</span>
                <strong>{{ $customerName }}</strong>
                <small>{{ $customer?->city ?: 'City not set' }}</small>
            </div>

            <div class="quote-detail">
                <span>Service</span>
                <strong>{{ $quote?->service_needed ?? 'Solar installation' }}</strong>
            </div>

            <div class="quote-detail">
                <span>Selected offer</span>
                <strong>
                    @if ($offer)
                        {{ $offer->currency }}
                        {{ number_format((float) $offer->price, 2) }}
                    @else
                        —
                    @endif
                </strong>
            </div>

            <div class="quote-detail">
                <span>Project status</span>
                <strong>
                    <span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">
                        {{ $statusLabels[$project->status] ?? ucfirst($project->status) }}
                    </span>
                </strong>
            </div>

            <div class="quote-detail">
                <span>Last update</span>
                <strong>
                    {{ $project->updated_at?->format('d M Y, H:i') ?? '—' }}
                </strong>
                <small>{{ $project->updated_at?->diffForHumans() }}</small>
            </div>
        </div>

        {{-- Contact details for arranging site visits and installation --}}
        <div class="project-contact">
            <span class="project-contact-title">Customer contact</span>
            <div class="project-contact-items">
                @if ($customer?->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}">📞 {{ $customer->phone }}</a>
                @endif
                @if ($customer?->email)
                    <a href="mailto:{{ $customer->email }}">✉ {{ $customer->email }}</a>
                @endif
                <span>📍 {{ collect([$customer?->address, $customer?->city])->filter()->implode(', ') ?: 'Address not provided' }}</span>
            </div>
        </div>
    </section>

    {{-- Three-stage project tracker --}}
    <section class="panel" style="margin-top:16px;padding:24px">
        <div class="project-stage-tracker">
            @foreach ($stages as $index => $stage)
                @php
                    $isCompletedStage = $index < $currentStage;
                    $isCurrentStage = $index === $currentStage;
                @endphp

                <div class="project-stage
                    {{ $isCompletedStage ? 'is-complete' : '' }}
                    {{ $isCurrentStage ? 'is-current' : '' }}">

                    <span class="project-stage-number">
                        @if ($isCompletedStage)
                            ✓
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>

                    <div>
                        <strong>
                            {{ $index + 1 }}. {{ $statusLabels[$stage] }}
                        </strong>

                        <small>
                            @if ($stage === 'accepted')
                                Offer accepted by customer
                            @elseif ($stage === 'in_progress')
                                Installation and handover process
                            @else
                                Installation completed and customer handover confirmed
                            @endif
                        </small>

                        <small>
                            @if ($stage === 'accepted')
                                {{ $project->accepted_at?->format('d M Y, H:i') ?? '—' }}
                            @elseif ($stage === 'in_progress')
                                {{ $project->started_at?->format('d M Y, H:i') ?? '—' }}
                            @else
                                {{ $project->completed_at?->format('d M Y, H:i') ?? '—' }}
                            @endif
                        </small>
                    </div>
                </div>

                @if (!$loop->last)
                    <div class="project-stage-connector
                        {{ $index < $currentStage ? 'is-complete' : '' }}">
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    <div class="project-management-grid" style="margin-top:16px">

        {{-- Left column --}}
        <div class="project-main-column">

            {{-- Installation checklist --}}
            <section class="panel table-panel">
                <div class="panel-heading">
                    <h2>Installation checklist</h2>

                    <p>
                        @if ($isAccepted)
                            Review the required phases. Start installation to enable updates.
                        @elseif ($isCompleted)
                            All project phases have been completed and locked.
                        @elseif ($canUpdateHandover)
                            Installation is finished. Customer handover is the final step.
                        @else
                            Complete the current installation phase before moving forward.
                        @endif
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Milestone</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Completed</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($milestones as $milestone)
                                @php
                                    $isHandoverMilestone =
                                        strtolower(trim($milestone->title)) === 'customer handover';
                                @endphp

                                <tr>
                                    <td>{{ $milestone->sort_order ?: $loop->iteration }}</td>

                                    <td>
                                        <strong>{{ $milestone->title }}</strong>

                                        @if ($isHandoverMilestone && !$installationComplete)
                                            <small style="display:block;margin-top:4px">
                                                Available after installation is complete
                                            </small>
                                        @endif
                                    </td>

                                    <td>{{ $milestone->description ?: '—' }}</td>

                                    <td>
                                        <span class="pill {{ $milestoneClasses[$milestone->status] ?? 'pill-blue' }}">
                                            {{ $milestoneLabels[$milestone->status] ?? ucfirst($milestone->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $milestone->completed_at?->format('d M Y') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:28px">
                                        No milestones have been created for this project.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Activity history --}}
            <section class="panel" style="margin-top:16px;padding:24px">
                <div class="panel-heading">
                    <h2>Activity history</h2>
                    <p>Timeline of project updates and important milestones.</p>
                </div>

                <div class="project-activity-list">
                    @forelse ($project->updates as $update)
                        <article class="project-activity-item">
                            <span class="project-activity-dot"></span>

                            <div class="project-activity-content">
                                <div class="project-activity-meta">
                                    <strong>
                                        @if ($update->old_status !== $update->new_status && $update->new_status)
                                            Project status changed to
                                            {{ $statusLabels[$update->new_status] ?? ucfirst(str_replace('_', ' ', $update->new_status)) }}
                                        @else
                                            Project update
                                        @endif
                                    </strong>

                                    <small>
                                        {{ $update->created_at?->format('d M Y, H:i') }}
                                    </small>
                                </div>

                                @if ($update->progress_percent !== null)
                                    <small>Progress: {{ $update->progress_percent }}%</small>
                                @endif

                                @if ($update->message)
                                    <p>{{ $update->message }}</p>
                                @endif

                                @if (!$update->visible_to_customer)
                                    <span class="pill pill-purple">Internal note</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p>No project updates have been recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Right column: project workflow --}}
        <aside class="project-side-column">
            <section class="panel" style="padding:24px">

                <div class="panel-heading">
                    <h2>
                        @if ($isAccepted)
                            Prepare project
                        @elseif ($isCompleted)
                            Project completed
                        @elseif ($canUpdateHandover)
                            Customer handover
                        @else
                            Current phase
                        @endif
                    </h2>

                    <p>
                        @if ($isAccepted)
                            The installation checklist is read-only until work starts.
                        @elseif ($isCompleted)
                            This project is closed and can no longer be edited.
                        @elseif ($canUpdateHandover)
                            Confirm the final handover to complete the project.
                        @else
                            Update the current installation milestone below.
                        @endif
                    </p>
                </div>

                {{-- ACCEPTED: read-only until the company starts installation --}}
                @if ($isAccepted)
                    <div class="project-next-action">
                        <strong>Next action: Start installation</strong>
                        <p>
                            Confirm that your company is ready to begin work.
                            After starting, you can update installation milestones.
                        </p>
                    </div>

                        <form method="POST"
                            action="{{ route('company.projects.progress.update', $project->id) }}">
                            @csrf

                            <input type="hidden" name="status" value="in_progress">
                            <input type="hidden" name="progress_percent" value="0">

                            <button type="submit" class="btn btn-primary">
                                Start Installation
                            </button>
                        </form>

                {{-- COMPLETED: permanently read-only --}}
                @elseif ($isCompleted)
                    <div class="project-next-action project-next-action-success">
                        <strong>Project successfully completed</strong>

                        <p>
                            Installation and customer handover have been recorded.
                            All project controls are permanently locked.
                        </p>

                        <span class="pill pill-green">100% Complete</span>
                    </div>

                {{-- IN PROGRESS: installation or handover --}}
                @elseif ($isInProgress)

                    @if ($canUpdateHandover)
                        <div class="project-next-action project-next-action-success">
                            <strong>Final step: Customer handover</strong>

                            <p>
                                Confirm that the solar system has been handed over
                                to the customer and that the customer understands
                                how to operate it.
                            </p>
                        </div>

                        <div class="alert alert-danger" role="alert" style="margin-bottom:18px">
                            <strong>Important — irreversible action</strong>
                            <p style="margin:6px 0 0">
                                Confirming customer handover will mark this project
                                as Completed, set progress to 100%, and permanently
                                lock all project updates. Verify the installation
                                and handover before confirming.
                            </p>
                        </div>

                    @elseif ($currentMilestone)
                        <div class="project-next-action">
                            <small>CURRENT PHASE</small>
                            <strong>{{ $currentMilestone->title }}</strong>

                            <p>
                                {{ $currentMilestone->description
                                    ?: 'Update this milestone when the work is complete.' }}
                            </p>
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('company.projects.progress.update', $project->id) }}">
                        @csrf

                        {{-- Status is controlled by the workflow, not a dropdown. --}}
                        <input type="hidden" name="status" value="in_progress">
                        <input type="hidden" name="progress_percent" value="{{ $progress }}">

                        {{-- Dynamic progress: calculated from installation milestones. --}}
                        <div class="form-group" style="margin-bottom:22px">
                            <label>Project completion</label>

                            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin:8px 0 12px">
                                <span>{{ $progressLabel }}</span>
                                <strong>{{ $progress }}%</strong>
                            </div>

                            <div class="project-progress-track"
                                 role="progressbar"
                                 aria-valuenow="{{ $progress }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-label="Project completion">
                                <div class="project-progress-fill"
                                     style="width:{{ $progress }}%">
                                </div>
                            </div>

                            <small style="display:block;margin-top:10px">
                                {{ $completedInstallationMilestones }}
                                of {{ $installationTotal }} installation phases completed.

                                @if ($installationComplete)
                                    Customer handover is required to reach 100%.
                                @else
                                    Progress updates automatically when milestones change.
                                @endif
                            </small>
                        </div>

                        {{-- Milestone controls --}}
                        <div class="form-group" style="margin-top:20px">
                            <label>Update milestone status</label>

                            @foreach ($milestones as $milestone)
                                @php
                                    $isHandoverMilestone =
                                        strtolower(trim($milestone->title)) === 'customer handover';

                                    $isAlreadyCompleted =
                                        $milestone->status === 'completed';

                                    $isHandoverLocked =
                                        $isHandoverMilestone && !$installationComplete;

                                    $isLocked =
                                        $isAlreadyCompleted || $isHandoverLocked;
                                @endphp

                                <div class="project-milestone-field">
                                    <strong>{{ $milestone->title }}</strong>

                                    @if ($milestone->description)
                                        <small>{{ $milestone->description }}</small>
                                    @endif

                                    @if ($isHandoverLocked)
                                        <p class="project-field-hint">
                                            Complete every installation phase before
                                            confirming customer handover.
                                        </p>

                                        <input
                                            type="hidden"
                                            name="milestones[{{ $milestone->id }}][status]"
                                            value="{{ $milestone->status }}"
                                        >
                                    @else
                                        <select
                                            name="milestones[{{ $milestone->id }}][status]"
                                            class="form-control"
                                            @disabled($isLocked)
                                        >
                                            <option value="pending"
                                                @selected(old("milestones.{$milestone->id}.status", $milestone->status) === 'pending')>
                                                Not started
                                            </option>

                                            <option value="in_progress"
                                                @selected(old("milestones.{$milestone->id}.status", $milestone->status) === 'in_progress')>
                                                In progress
                                            </option>

                                            <option value="completed"
                                                @selected(old("milestones.{$milestone->id}.status", $milestone->status) === 'completed')>
                                                Completed
                                            </option>
                                        </select>

                                        {{-- Disabled controls are not submitted, so preserve their values. --}}
                                        @if ($isLocked)
                                            <input
                                                type="hidden"
                                                name="milestones[{{ $milestone->id }}][status]"
                                                value="completed"
                                            >
                                        @endif
                                    @endif

                                    @if ($isHandoverMilestone && !$handoverComplete && $installationComplete)
                                        <small class="project-field-hint">
                                            Selecting Completed confirms the final handover
                                            and closes the project permanently.
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Update message --}}
                        <div class="form-group" style="margin-top:20px">
                            <label for="message">Update message</label>

                            <textarea
                                name="message"
                                id="message"
                                class="form-control"
                                rows="4"
                                maxlength="500"
                                placeholder="{{ $canUpdateHandover
                                    ? 'Confirm the handover details for the customer...'
                                    : 'Describe the work completed or the next step...' }}"
                            >{{ old('message') }}</textarea>

                            <small>Maximum 500 characters. The customer sees this message in the app.</small>
                        </div>

                        {{-- Customer visibility --}}
                        {{-- <div class="form-group" style="margin-top:16px">
                            <label>
                                <input
                                    type="checkbox"
                                    name="visible_to_customer"
                                    value="1"
                                    @checked(old('visible_to_customer', true))
                                >
                                Visible to customer
                            </label>
                        </div> --}}

                        <button type="submit"
                                class="btn btn-primary"
                                style="width:100%;margin-top:16px"
                                @if ($canUpdateHandover)
                                    onclick="return confirm('FINAL CONFIRMATION: Customer handover cannot be reversed. Confirming it will complete and permanently lock this project. Have you verified the installation and handed the system over to the customer?');"
                                @endif>
                            {{ $canUpdateHandover
                                ? 'Confirm Customer Handover'
                                : 'Save Progress' }}
                        </button>
                    </form>
                @endif
            </section>
        </aside>
    </div>
@endsection
