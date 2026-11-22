
@extends('company.layouts.app')

@section('title', __('company/messages.projects.page_title'))
@section('breadcrumb', __('company/messages.projects.title'))
@section('eyebrow', __('company/messages.projects.eyebrow'))
@section('page-title', __('company/messages.projects.page_title'))
@section('page-description', __('company/messages.projects.show_description'))

@section('page-actions')
    <div class="quote-page-actions">
        <a href="{{ route('company.projects.index') }}" class="btn btn-light">
            {{ __('company/messages.projects.back') }}
        </a>
    </div>
@endsection

@section('content')
    @php
        $quote = $project->quote;
        $customer = $project->customer;
        $offer = $project->companyQuotation;

        $customerName = $customer?->full_name
            ?: ($customer?->username ?: __('company/messages.common.customer_number', ['id' => $project->customer_id]));

        $reference = '#Q-' . str_pad(
            (string) $project->quote_id,
            5,
            '0',
            STR_PAD_LEFT
        );

        $statusLabels = [
            'accepted' => __('company/messages.common.accepted'),
            'in_progress' => __('company/messages.common.in_progress'),
            'handover_pending' => __('company/messages.common.handover_pending'),
            'completed' => __('company/messages.common.completed'),
        ];

        $statusClasses = [
            'accepted' => 'pill-orange',
            'in_progress' => 'pill-blue',
            'handover_pending' => 'pill-purple',
            'completed' => 'pill-green',
        ];

        $stages = ['accepted', 'in_progress', 'handover_pending', 'completed'];

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
        // The company finished; locked until the customer confirms the handover in the app.
        $isHandoverPending = $project->status === 'handover_pending';

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
            $progressLabel = __('company/messages.projects.project_completed');
        } elseif ($isHandoverPending) {
            $progress = 100;
            $progressLabel = __('company/messages.common.handover_pending');
        } elseif ($isAccepted) {
            $progress = 0;
            $progressLabel = __('company/messages.projects.waiting_to_start');
        } elseif ($installationComplete) {
            $progress = 99;
            $progressLabel = $handoverComplete
                ? __('company/messages.projects.handover_recorded')
                : __('company/messages.projects.installation_complete_pending');
        } elseif ($installationTotal > 0) {
            $progress = (int) floor(
                ($completedInstallationMilestones / $installationTotal) * 99
            );

            $progressLabel = __('company/messages.projects.installation_phases');
        } else {
            $progress = 0;
            $progressLabel = __('company/messages.projects.phases_pending');
        }

        $milestoneLabels = [
            'pending' => __('company/messages.common.not_started'),
            'in_progress' => __('company/messages.common.in_progress'),
            'completed' => __('company/messages.common.completed'),
        ];

        $milestoneClasses = [
            'pending' => 'pill-blue',
            'in_progress' => 'pill-yellow',
            'completed' => 'pill-green',
        ];
    @endphp

    {{-- The customer reported a problem instead of confirming the handover --}}
    @if ($isInProgress && $project->handover_problem_at)
        <div class="alert alert-danger" role="alert">
            <strong>{{ __('company/messages.projects.problem_title', ['date' => $project->handover_problem_at->translatedFormat('d M Y, H:i')]) }}</strong>
            <p style="margin:6px 0">“{{ $project->handover_problem_message }}”</p>
            <small>{{ __('company/messages.projects.problem_body') }}</small>
        </div>
    @endif

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
            <strong>{{ __('company/messages.projects.unable_update') }}</strong>
            <ul style="margin:8px 0 0;padding-left:20px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('partials.page-guide', [
        'key' => 'company-project-details',
        'title' => __('company/messages.projects.guide_title'),
        'intro' => __('company/messages.projects.guide_intro'),
        'steps' => [
            __('company/messages.projects.guide_1'),
            __('company/messages.projects.guide_2'),
            __('company/messages.projects.guide_3'),
        ],
        'note' => __('company/messages.projects.guide_note'),
    ])

    {{-- Project summary --}}
    <section class="panel">
        <div class="quote-detail-grid">
            <div class="quote-detail">
                <span>{{ __('company/messages.common.reference') }}</span>
                <strong>
                    <a href="{{ route('company.quote-requests.show', $project->quote_id) }}">{{ $reference }}</a>
                </strong>
                <small>{{ __('company/messages.common.project_number', ['id' => $project->id]) }}</small>
            </div>

            <div class="quote-detail">
                <span>{{ __('company/messages.common.customer') }}</span>
                <strong>{{ $customerName }}</strong>
                <small>{{ $customer?->city ?: __('company/messages.common.city_not_set') }}</small>
            </div>

            <div class="quote-detail">
                <span>{{ __('company/messages.common.quote_name') }}</span>
                <strong>{{ $quote?->quote_name ?? __('company/messages.common.solar_installation') }}</strong>
            </div>

            <div class="quote-detail">
                <span>{{ __('company/messages.common.selected_offer') }}</span>
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
                <span>{{ __('company/messages.projects.project_status') }}</span>
                <strong>
                    <span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">
                        {{ $statusLabels[$project->status] ?? ucfirst($project->status) }}
                    </span>
                </strong>
            </div>

            <div class="quote-detail">
                <span>{{ __('company/messages.common.last_update') }}</span>
                <strong>
                    {{ $project->updated_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                </strong>
                <small>{{ $project->updated_at?->diffForHumans() }}</small>
            </div>
        </div>

        {{-- Contact details for arranging site visits and installation --}}
        <div class="project-contact">
            <span class="project-contact-title">{{ __('company/messages.projects.customer_contact') }}</span>
            <div class="project-contact-items">
                @if ($customer?->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}">📞 {{ $customer->phone }}</a>
                @endif
                @if ($customer?->email)
                    <a href="mailto:{{ $customer->email }}">✉ {{ $customer->email }}</a>
                @endif
                <span>📍 {{ collect([$customer?->address, $customer?->city])->filter()->implode(', ') ?: __('company/messages.projects.address_missing') }}</span>
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
                                {{ __('company/messages.projects.stage_accepted') }}
                            @elseif ($stage === 'in_progress')
                                {{ __('company/messages.projects.stage_progress') }}
                            @elseif ($stage === 'handover_pending')
                                {{ __('company/messages.projects.stage_handover_pending') }}
                            @else
                                {{ __('company/messages.projects.stage_completed') }}
                            @endif
                        </small>

                        <small>
                            @if ($stage === 'accepted')
                                {{ $project->accepted_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                            @elseif ($stage === 'in_progress')
                                {{ $project->started_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                            @elseif ($stage === 'handover_pending')
                                {{ $project->handover_requested_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                            @else
                                {{ $project->completed_at?->translatedFormat('d M Y, H:i') ?? '—' }}
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
                    <h2>{{ __('company/messages.projects.checklist') }}</h2>

                    <p>
                        @if ($isAccepted)
                            {{ __('company/messages.projects.checklist_accepted') }}
                        @elseif ($isCompleted || $isHandoverPending)
                            {{ __('company/messages.projects.checklist_completed') }}
                        @elseif ($canUpdateHandover)
                            {{ __('company/messages.projects.checklist_handover') }}
                        @else
                            {{ __('company/messages.projects.checklist_current') }}
                        @endif
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('company/messages.projects.milestone') }}</th>
                                <th>{{ __('company/messages.projects.description') }}</th>
                                <th>{{ __('company/messages.common.status') }}</th>
                                <th>{{ __('company/messages.common.completed') }}</th>
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
                                        <strong>{{ $milestone->displayTitle() }}</strong>

                                        @if ($isHandoverMilestone && !$installationComplete)
                                            <small style="display:block;margin-top:4px">
                                                {{ __('company/messages.projects.available_after') }}
                                            </small>
                                        @endif
                                    </td>

                                    <td>{{ $milestone->displayDescription() ?: '—' }}</td>

                                    <td>
                                        <span class="pill {{ $milestoneClasses[$milestone->status] ?? 'pill-blue' }}">
                                            {{ $milestoneLabels[$milestone->status] ?? ucfirst($milestone->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $milestone->completed_at?->translatedFormat('d M Y') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:28px">
                                        {{ __('company/messages.projects.no_milestones') }}
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
                    <h2>{{ __('company/messages.projects.activity_history') }}</h2>
                    <p>{{ __('company/messages.projects.activity_sub') }}</p>
                </div>

                <div class="project-activity-list">
                    @forelse ($project->updates as $update)
                        <article class="project-activity-item">
                            <span class="project-activity-dot"></span>

                            <div class="project-activity-content">
                                <div class="project-activity-meta">
                                    <strong>
                                        @if ($update->old_status !== $update->new_status && $update->new_status)
                                            {{ __('company/messages.projects.status_changed', ['status' => $statusLabels[$update->new_status] ?? ucfirst(str_replace('_', ' ', $update->new_status))]) }}
                                        @else
                                            {{ __('company/messages.projects.project_update') }}
                                        @endif
                                    </strong>

                                    <small>
                                        {{ $update->created_at?->translatedFormat('d M Y, H:i') }}
                                    </small>
                                </div>

                                @if ($update->progress_percent !== null)
                                    <small>{{ __('company/messages.projects.progress_line', ['percent' => $update->progress_percent]) }}</small>
                                @endif

                                @if ($update->message)
                                    <p>{{ $update->message }}</p>
                                @endif

                                @if (!$update->visible_to_customer)
                                    <span class="pill pill-purple">{{ __('company/messages.projects.internal_note') }}</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p>{{ __('company/messages.projects.no_updates') }}</p>
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
                            {{ __('company/messages.projects.prepare_project') }}
                        @elseif ($isCompleted)
                            {{ __('company/messages.projects.project_completed') }}
                        @elseif ($isHandoverPending)
                            {{ __('company/messages.common.handover_pending') }}
                        @elseif ($canUpdateHandover)
                            {{ __('company/messages.projects.customer_handover') }}
                        @else
                            {{ __('company/messages.projects.current_phase') }}
                        @endif
                    </h2>

                    <p>
                        @if ($isAccepted)
                            {{ __('company/messages.projects.side_accepted') }}
                        @elseif ($isCompleted)
                            {{ __('company/messages.projects.side_completed') }}
                        @elseif ($isHandoverPending)
                            {{ __('company/messages.projects.side_handover_pending') }}
                        @elseif ($canUpdateHandover)
                            {{ __('company/messages.projects.side_handover') }}
                        @else
                            {{ __('company/messages.projects.side_current') }}
                        @endif
                    </p>
                </div>

                {{-- ACCEPTED: read-only until the company starts installation --}}
                @if ($isAccepted)
                    <div class="project-next-action">
                        <strong>{{ __('company/messages.projects.next_start') }}</strong>
                        <p>
                            {{ __('company/messages.projects.next_start_body') }}
                        </p>
                    </div>

                    @if ($project->pendingPriceChange)
                        {{-- The price is not agreed until the customer answers the new price. --}}
                        <p class="project-field-hint">{{ __('company/messages.deal.err_price_pending_start') }}</p>
                    @else
                        <form method="POST"
                            action="{{ route('company.projects.progress.update', $project->id) }}">
                            @csrf

                            <input type="hidden" name="status" value="in_progress">
                            <input type="hidden" name="progress_percent" value="0">

                            <button type="submit" class="btn btn-primary">
                                {{ __('company/messages.projects.start_button') }}
                            </button>
                        </form>
                    @endif

                {{-- COMPLETED: permanently read-only --}}
                @elseif ($isCompleted)
                    <div class="project-next-action project-next-action-success">
                        <strong>{{ __('company/messages.projects.success_title') }}</strong>

                        <p>
                            {{ __('company/messages.projects.success_body') }}
                        </p>

                        <span class="pill pill-green">{{ __('company/messages.projects.complete_100') }}</span>
                    </div>

                {{-- HANDOVER PENDING: the company is done; the customer confirms in the app --}}
                @elseif ($isHandoverPending)
                    <div class="project-next-action">
                        <strong>{{ __('company/messages.projects.waiting_customer_title') }}</strong>

                        <p>
                            {{ __('company/messages.projects.waiting_customer_body') }}
                        </p>

                        @if ($autoConfirmAt = $project->handoverAutoConfirmAt())
                            <p>
                                {{ __('company/messages.projects.waiting_customer_auto', [
                                    'days' => \App\Models\Project::AUTO_CONFIRM_DAYS,
                                    'date' => $autoConfirmAt->translatedFormat('d M Y, H:i'),
                                ]) }}
                            </p>
                        @endif

                        <span class="pill pill-purple">{{ __('company/messages.common.handover_pending') }}</span>
                    </div>

                {{-- IN PROGRESS: installation or handover --}}
                @elseif ($isInProgress)

                    @if ($canUpdateHandover)
                        <div class="project-next-action project-next-action-success">
                            <strong>{{ __('company/messages.projects.final_step') }}</strong>

                            <p>
                                {{ __('company/messages.projects.final_body') }}
                            </p>
                        </div>

                        <div class="alert alert-danger" role="alert" style="margin-bottom:18px">
                            <strong>{{ __('company/messages.projects.important') }}</strong>
                            <p style="margin:6px 0 0">
                                {{ __('company/messages.projects.important_body') }}
                            </p>
                        </div>

                    @elseif ($currentMilestone)
                        <div class="project-next-action">
                            <small>{{ __('company/messages.projects.current_phase_caps') }}</small>
                            <strong>{{ $currentMilestone->displayTitle() }}</strong>

                            <p>
                                {{ $currentMilestone->displayDescription()
                                    ?: __('company/messages.projects.milestone_default') }}
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
                            <label>{{ __('company/messages.projects.project_completion') }}</label>

                            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin:8px 0 12px">
                                <span>{{ $progressLabel }}</span>
                                <strong>{{ $progress }}%</strong>
                            </div>

                            <div class="project-progress-track"
                                 role="progressbar"
                                 aria-valuenow="{{ $progress }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-label="{{ __('company/messages.projects.project_completion') }}">
                                <div class="project-progress-fill"
                                     style="width:{{ $progress }}%">
                                </div>
                            </div>

                            <small style="display:block;margin-top:10px">
                                {{ __('company/messages.projects.phases_of', ['done' => $completedInstallationMilestones, 'total' => $installationTotal]) }}

                                @if ($installationComplete)
                                    {{ __('company/messages.projects.handover_required') }}
                                @else
                                    {{ __('company/messages.projects.auto_progress') }}
                                @endif
                            </small>
                        </div>

                        {{-- Milestone controls --}}
                        <div class="form-group" style="margin-top:20px">
                            <label>{{ __('company/messages.projects.update_milestones') }}</label>

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
                                    <strong>{{ $milestone->displayTitle() }}</strong>

                                    @if ($milestone->description)
                                        <small>{{ $milestone->displayDescription() }}</small>
                                    @endif

                                    @if ($isHandoverLocked)
                                        <p class="project-field-hint">
                                            {{ __('company/messages.projects.handover_locked') }}
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
                                                {{ __('company/messages.common.not_started') }}
                                            </option>

                                            <option value="in_progress"
                                                @selected(old("milestones.{$milestone->id}.status", $milestone->status) === 'in_progress')>
                                                {{ __('company/messages.common.in_progress') }}
                                            </option>

                                            <option value="completed"
                                                @selected(old("milestones.{$milestone->id}.status", $milestone->status) === 'completed')>
                                                {{ __('company/messages.common.completed') }}
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
                                            {{ __('company/messages.projects.handover_warning') }}
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Update message --}}
                        <div class="form-group" style="margin-top:20px">
                            <label for="message">{{ __('company/messages.projects.update_message') }}</label>

                            <textarea
                                name="message"
                                id="message"
                                class="form-control"
                                rows="4"
                                maxlength="500"
                                placeholder="{{ $canUpdateHandover
                                    ? __('company/messages.projects.placeholder_handover')
                                    : __('company/messages.projects.placeholder_update') }}"
                            >{{ old('message') }}</textarea>

                            <small>{{ __('company/messages.projects.message_hint') }}</small>
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
                                    onclick="return confirm(@js(__('company/messages.projects.final_confirm')));"
                                @endif>
                            {{ $canUpdateHandover
                                ? __('company/messages.projects.confirm_handover_button')
                                : __('company/messages.projects.save_progress') }}
                        </button>
                    </form>
                @endif
            </section>

            {{-- Backing out of the deal, or asking the customer for a new price --}}
            @unless ($isCompleted)
                @php
                    $priceChange = $project->pendingPriceChange;
                    $openRequest = $project->openCancellationRequest;
                    $lastCancellation = $project->latestCancellation;
                    $isFlagged = $withdrawals['flagged'] ?? false;
                    $money = fn ($value) => number_format((float) $value) . ' ' . ($offer?->currency ?? 'SDG');
                    $withdrawNow = $isAccepted && !$isFlagged;
                @endphp

                <section class="panel" style="margin-top:16px;padding:24px">
                    <div class="panel-heading">
                        <h2>{{ __('company/messages.deal.panel_title') }}</h2>
                        <p>{{ __('company/messages.deal.panel_sub') }}</p>
                    </div>

                    <p class="project-field-hint" @if ($isFlagged) style="color:#c92a2a" @endif>
                        {{ $isFlagged
                            ? __('company/messages.deal.standing_flagged', ['count' => $withdrawals['count'], 'days' => $withdrawals['window_days']])
                            : __('company/messages.deal.standing', ['count' => $withdrawals['count'], 'threshold' => $withdrawals['threshold'], 'days' => $withdrawals['window_days']]) }}
                    </p>

                    @if ($priceChange)
                        <div class="project-next-action">
                            <strong>{{ __('company/messages.deal.price_pending_title') }}</strong>
                            <p>
                                {{ __('company/messages.deal.price_pending_body', [
                                    'date' => $priceChange->created_at?->translatedFormat('d M Y, H:i'),
                                    'old' => $money($priceChange->old_price),
                                    'new' => $money($priceChange->new_price),
                                ]) }}
                            </p>
                            <form method="POST" action="{{ route('company.projects.price-change.cancel', $project->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-light">{{ __('company/messages.deal.price_cancel_button') }}</button>
                            </form>
                        </div>
                    @endif

                    @if ($openRequest)
                        <div class="project-next-action">
                            <strong>{{ __('company/messages.deal.request_open_title', ['date' => $openRequest->created_at?->translatedFormat('d M Y, H:i')]) }}</strong>
                            <p>{{ __('company/messages.deal.request_open_body', ['reason' => __('company/messages.deal.reasons.' . $openRequest->reason)]) }}</p>
                            <form method="POST" action="{{ route('company.projects.withdraw.cancel', $project->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-light">{{ __('company/messages.deal.request_cancel_button') }}</button>
                            </form>
                        </div>
                    @else
                        @if ($lastCancellation?->status === \App\Models\ProjectCancellation::REJECTED)
                            <div class="alert alert-danger" role="alert">
                                <strong>{{ __('company/messages.deal.request_rejected_title', ['date' => $lastCancellation->decided_at?->translatedFormat('d M Y, H:i')]) }}</strong>
                                @if ($lastCancellation->admin_note)
                                    <p style="margin:6px 0 0">{{ __('company/messages.deal.request_rejected_note', ['note' => $lastCancellation->admin_note]) }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Change the price: only before work starts, one at a time --}}
                        @if ($isAccepted && !$priceChange && $offer)
                            <details style="margin-top:12px" @if ($errors->has('new_price')) open @endif>
                                <summary><strong>{{ __('company/messages.deal.price_title') }}</strong></summary>
                                <p class="project-field-hint">{{ __('company/messages.deal.price_body') }}</p>
                                <form method="POST" action="{{ route('company.projects.price-change', $project->id) }}">
                                    @csrf
                                    <p class="project-field-hint">{{ __('company/messages.deal.current_price', ['price' => $money($offer->price)]) }}</p>
                                    <div class="form-group">
                                        <label for="new_price">{{ __('company/messages.deal.new_price', ['currency' => $offer->currency]) }}</label>
                                        <input type="number" name="new_price" id="new_price" class="form-control" min="1000" step="0.01"
                                            value="{{ old('new_price') }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="price_reason">{{ __('company/messages.deal.reason') }}</label>
                                        <select name="reason" id="price_reason" class="form-control" required>
                                            <option value="">{{ __('company/messages.deal.choose_reason') }}</option>
                                            @foreach (array_keys(\App\Models\OfferPriceChange::REASONS) as $reason)
                                                <option value="{{ $reason }}">{{ __('company/messages.deal.price_reasons.' . $reason) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="price_note">{{ __('company/messages.deal.note') }}</label>
                                        <textarea name="note" id="price_note" class="form-control" rows="3" maxlength="1000"></textarea>
                                        <small>{{ __('company/messages.deal.note_price_hint') }}</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%">{{ __('company/messages.deal.price_button') }}</button>
                                </form>
                            </details>
                        @endif

                        {{-- Withdraw: right away before work starts, otherwise a request for SunXFinder --}}
                        <details style="margin-top:12px" @if ($errors->has('reason') || $errors->has('note')) open @endif>
                            <summary><strong>{{ __('company/messages.deal.withdraw_title') }}</strong></summary>
                            <p class="project-field-hint">
                                {{ $withdrawNow
                                    ? __('company/messages.deal.withdraw_body_now')
                                    : ($isAccepted ? __('company/messages.deal.withdraw_body_flagged') : __('company/messages.deal.withdraw_body_request')) }}
                            </p>
                            <form method="POST" action="{{ route('company.projects.withdraw', $project->id) }}"
                                @if ($withdrawNow) onsubmit="return confirm(@js(__('company/messages.deal.withdraw_confirm')));" @endif>
                                @csrf
                                <div class="form-group">
                                    <label for="withdraw_reason">{{ __('company/messages.deal.reason') }}</label>
                                    <select name="reason" id="withdraw_reason" class="form-control" required>
                                        <option value="">{{ __('company/messages.deal.choose_reason') }}</option>
                                        @foreach (array_keys(\App\Models\ProjectCancellation::REASONS) as $reason)
                                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ __('company/messages.deal.reasons.' . $reason) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="withdraw_note">{{ __('company/messages.deal.note') }}</label>
                                    <textarea name="note" id="withdraw_note" class="form-control" rows="3" maxlength="1000"
                                        @unless ($withdrawNow) required minlength="10" @endunless>{{ old('note') }}</textarea>
                                    <small>{{ __('company/messages.deal.note_withdraw_hint') }}</small>
                                </div>
                                <button type="submit" class="btn btn-light" style="width:100%;color:#c92a2a;border-color:#c92a2a">
                                    {{ $withdrawNow ? __('company/messages.deal.withdraw_button') : __('company/messages.deal.request_button') }}
                                </button>
                            </form>
                        </details>
                    @endif
                </section>
            @endunless
        </aside>
    </div>
@endsection
