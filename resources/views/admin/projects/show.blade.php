@extends('admin.layouts.app')

@section('title', 'Project #' . $project->id)
@section('breadcrumb', 'Projects / #' . $project->id)
@section('eyebrow', 'PROJECT OVERSIGHT')
@section('page-title', 'Project #' . $project->id)
@section('page-description', 'Review the complete project timeline, milestones, and customer or company follow-up details.')

@section('page-actions')
    <div class="quote-page-actions">
        <a href="{{ route('admin.projects.index') }}" class="btn btn-light">← All projects</a>
        <a href="{{ route('admin.quote-requests.show', $project->quote_id) }}" class="btn btn-primary">Open quote request</a>
    </div>
@endsection

@section('content')
    @php
        $company = $project->company;
        $customer = $project->customer;
        $quote = $project->quote;
        $offer = $project->companyQuotation;
        $progress = max(0, min(100, (int) $project->progress_percent));
        $statusLabels = ['accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed'];
        $statusClasses = ['accepted' => 'pill-orange', 'in_progress' => 'pill-blue', 'completed' => 'pill-green'];
        $milestoneLabels = ['pending' => 'Not started', 'in_progress' => 'In progress', 'completed' => 'Completed'];
        $milestoneClasses = ['pending' => 'pill-blue', 'in_progress' => 'pill-yellow', 'completed' => 'pill-green'];
        $customerName = $customer?->full_name ?: $customer?->username ?: 'Customer #' . $project->customer_id;
        $companyName = $company?->name ?: 'Company #' . $project->company_id;
        $milestoneTotal = $project->milestones->count();
        $milestoneDone = $project->milestones->where('status', 'completed')->count();
        $statusLabel = fn ($status) => $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', (string) $status));
        $initial = fn ($name) => mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
        $parties = [
            [
                'role' => 'Company',
                'name' => $companyName,
                'model' => $company,
                'url' => $company && !$company->trashed() ? route('admin.companies.details', $company->id) : null,
                'avatar' => 'avatar-orange',
            ],
            [
                'role' => 'Customer',
                'name' => $customerName,
                'model' => $customer,
                'url' => $customer && !$customer->trashed() ? route('admin.customers.edit', $customer->id) : null,
                'avatar' => 'avatar-blue',
            ],
        ];
        $dates = [
            'Accepted' => $project->accepted_at,
            'Installation started' => $project->started_at,
            'Completed' => $project->completed_at,
        ];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-project-details',
        'title' => 'How to read this project',
        'intro' => 'This page shows everything the company has recorded for this installation. Admins can review it here; progress and milestones are updated by the company.',
        'steps' => [
            '<strong>Overview</strong>: the current status, overall progress, accepted offer and the time of the last activity.',
            '<strong>Milestones</strong>: the installation steps. A green tick is completed, amber is in progress, and a number has not started yet.',
            '<strong>Update history</strong>: every company update, newest first. "Internal note" updates are hidden from the customer.',
            '<strong>Follow-up</strong>: if there is no update for ' . $stalledDays . ' days, the project is marked as stalled. Use the contact cards to follow up.',
        ],
        'note' => 'To reassign the company or act on the original request, use "Open quote request".',
    ])

    @if ($isStalled)
        <div class="alert alert-danger">
            <strong>Stalled project.</strong>
            No update has been recorded for {{ (int) $lastActivity->diffInDays(now()) }} days
            (the threshold is {{ $stalledDays }} days). Contact the company or customer below, or review the related quote request.
        </div>
    @endif

    <section class="panel admin-project-hero">
        <div class="admin-project-hero-top">
            <div class="admin-project-hero-title">
                <span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">{{ $statusLabel($project->status) }}</span>
                <h2>
                    <a href="{{ route('admin.quote-requests.show', $project->quote_id) }}">{{ $quote?->quote_name ?: 'Solar installation' }}</a>
                </h2>
                <p>Request #{{ $project->quote_id }} · {{ $companyName }} for {{ $customerName }}</p>
            </div>
            <div class="admin-project-progress">
                <div class="admin-project-progress-label">
                    <span>Overall progress</span>
                    <strong>{{ $progress }}%</strong>
                </div>
                <div class="mini-progress admin-project-progress-bar" role="progressbar" aria-valuenow="{{ $progress }}"
                    aria-valuemin="0" aria-valuemax="100" aria-label="Project progress">
                    <span style="width: {{ $progress }}%"></span>
                </div>
            </div>
        </div>

        <div class="admin-project-stats">
            <div>
                <small>Milestones</small>
                <strong>{{ $milestoneDone }} / {{ $milestoneTotal }}</strong>
                <span>completed</span>
            </div>
            <div>
                <small>Accepted offer</small>
                <strong>{{ $offer ? $offer->currency . ' ' . number_format((float) $offer->price, 2) : '—' }}</strong>
                <span>{{ $offer ? 'agreed price' : 'No offer linked' }}</span>
            </div>
            <div>
                <small>Updates</small>
                <strong>{{ $project->updates->count() }}</strong>
                <span>recorded</span>
            </div>
            <div>
                <small>Last activity</small>
                <strong class="{{ $isStalled ? 'admin-project-stalled' : '' }}">{{ $lastActivity?->diffForHumans() ?? '—' }}</strong>
                <span>{{ $lastActivity?->format('M d, Y H:i') ?? 'No activity recorded' }}</span>
            </div>
        </div>
    </section>

    <div class="admin-project-layout">
        <div class="admin-project-main">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Milestones</h2>
                        <p>{{ $milestoneDone }} of {{ $milestoneTotal }} completed.</p>
                    </div>
                </div>

                @if ($milestoneTotal)
                    <ol class="admin-project-milestones">
                        @foreach ($project->milestones as $milestone)
                            <li class="is-{{ $milestone->status }}">
                                <span class="admin-project-milestone-marker" aria-hidden="true">
                                    @if ($milestone->status === 'completed')
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                                            stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10" /></svg>
                                    @else
                                        {{ $milestone->sort_order ?: $loop->iteration }}
                                    @endif
                                </span>
                                <div class="admin-project-milestone-body">
                                    <div class="admin-project-milestone-head">
                                        <strong>{{ $milestone->title }}</strong>
                                        <span class="pill {{ $milestoneClasses[$milestone->status] ?? 'pill-blue' }}">
                                            {{ $milestoneLabels[$milestone->status] ?? ucfirst($milestone->status) }}
                                        </span>
                                    </div>
                                    @if ($milestone->description)
                                        <p>{{ $milestone->description }}</p>
                                    @endif
                                    @if ($milestone->started_at || $milestone->completed_at)
                                        <small>
                                            @if ($milestone->started_at)Started {{ $milestone->started_at->format('M d, Y H:i') }}@endif
                                            @if ($milestone->started_at && $milestone->completed_at) · @endif
                                            @if ($milestone->completed_at)Completed {{ $milestone->completed_at->format('M d, Y H:i') }}@endif
                                        </small>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="report-empty">No milestones recorded.</p>
                @endif
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Complete update history</h2>
                        <p>All company updates, newest first. Internal notes are visible to admins here.</p>
                    </div>
                </div>

                <div class="admin-project-timeline">
                    @forelse ($project->updates as $update)
                        @php $statusChanged = $update->new_status && $update->old_status !== $update->new_status; @endphp
                        <article class="admin-project-timeline-item {{ $statusChanged ? 'is-status' : '' }}">
                            <span class="admin-project-timeline-dot" aria-hidden="true"></span>
                            <div class="admin-project-timeline-content">
                                <div class="admin-project-timeline-meta">
                                    <strong>
                                        @if ($statusChanged)
                                            {{ $statusLabel($update->old_status ?: 'new') }} → {{ $statusLabel($update->new_status) }}
                                        @else
                                            Project update
                                        @endif
                                    </strong>
                                    <time datetime="{{ $update->created_at?->toIso8601String() }}"
                                        title="{{ $update->created_at?->format('M d, Y H:i:s') }}">
                                        {{ $update->created_at?->format('M d, Y H:i') }} · {{ $update->created_at?->diffForHumans() }}
                                    </time>
                                </div>
                                @if ($update->message)
                                    <p>{{ $update->message }}</p>
                                @endif
                                <div class="admin-project-timeline-tags">
                                    <span class="pill {{ $update->visible_to_customer ? 'pill-green' : 'pill-purple' }}">
                                        {{ $update->visible_to_customer ? 'Visible to customer' : 'Internal note' }}
                                    </span>
                                    @if ($update->progress_percent !== null)
                                        <span class="admin-project-tag">Progress {{ (int) $update->progress_percent }}%</span>
                                    @endif
                                    <span class="admin-project-tag">
                                        By {{ ucfirst($update->updated_by_type ?: 'system') }}{{ $update->updated_by_id ? ' #' . $update->updated_by_id : '' }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="report-empty">No project updates have been recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="admin-project-side">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Follow-up contacts</h2>
                        <p>Reach out directly, or use the quote request for assignment actions.</p>
                    </div>
                </div>

                <div class="admin-project-parties">
                    @foreach ($parties as $party)
                        @php $model = $party['model']; @endphp
                        <div class="admin-project-party">
                            <div class="admin-project-party-head">
                                <span class="person-avatar {{ $party['avatar'] }}">{{ $initial($party['name']) }}</span>
                                <div>
                                    <small>{{ $party['role'] }}</small>
                                    @if ($party['url'])
                                        <a href="{{ $party['url'] }}"><strong>{{ $party['name'] }}</strong></a>
                                    @else
                                        <strong>{{ $party['name'] }}</strong>
                                    @endif
                                    @if ($model?->trashed())
                                        <span class="pill pill-red">Deleted account</span>
                                    @elseif ($model)
                                        <span class="admin-project-party-city">{{ $model->city ?: 'City not set' }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="admin-project-party-links">
                                @if ($model?->phone)
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $model->phone) }}">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z" /></svg>
                                        {{ $model->phone }}
                                    </a>
                                @endif
                                @if ($model?->email)
                                    <a href="mailto:{{ $model->email }}">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" /></svg>
                                        {{ $model->email }}
                                    </a>
                                @endif
                                @if (!$model?->phone && !$model?->email)
                                    <small>No {{ strtolower($party['role']) }} contact details are available.</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Key dates</h2>
                    </div>
                </div>
                <dl class="admin-project-dates">
                    @foreach ($dates as $label => $date)
                        <div class="{{ $date ? 'is-done' : '' }}">
                            <dt>{{ $label }}</dt>
                            <dd>
                                {{ $date?->format('M d, Y H:i') ?? ($label === 'Accepted' ? '—' : 'Not yet') }}
                                @if ($date)<small>{{ $date->diffForHumans() }}</small>@endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </aside>
    </div>
@endsection
