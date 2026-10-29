@extends('admin.layouts.app')

@section('title', 'Projects')
@section('breadcrumb', 'Projects')
@section('eyebrow', 'PROJECT OVERSIGHT')
@section('page-title', request('stalled') ? 'Stalled Projects' : 'Projects')
@section('page-description', 'Track installation progress and follow up on projects across all companies.')

@section('page-actions')
    @if (request('stalled'))
        <a href="{{ route('admin.projects.index') }}" class="btn btn-light">All projects</a>
    @else
        <a href="{{ route('admin.projects.index', ['stalled' => 1]) }}" class="btn btn-light">Stalled projects</a>
    @endif
@endsection

@section('content')
    @php
        $filterKeys = ['status', 'company_id', 'customer', 'search', 'last_activity_from', 'last_activity_to', 'stalled'];
        $statusLabels = ['accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed'];
        $statusClasses = ['accepted' => 'pill-orange', 'in_progress' => 'pill-blue', 'completed' => 'pill-green'];
    @endphp

    <div class="metrics-strip">
        <div><small>All projects</small><strong>{{ number_format($metrics['total']) }}</strong></div>
        <div><small>Accepted</small><strong>{{ number_format($metrics['accepted']) }}</strong></div>
        <div><small>In progress</small><strong>{{ number_format($metrics['in_progress']) }}</strong></div>
        <div><small>Completed</small><strong>{{ number_format($metrics['completed']) }}</strong></div>
        <div>
            <small>Stalled ({{ $stalledDays }}+ days)</small>
            <strong class="{{ $metrics['stalled'] ? 'warning' : '' }}">
                <a href="{{ route('admin.projects.index', ['stalled' => 1]) }}">{{ number_format($metrics['stalled']) }}</a>
            </strong>
        </div>
    </div>

    @if (request('stalled'))
        <div class="alert alert-danger">
            Showing accepted or in-progress projects with no activity for at least {{ $stalledDays }} days.
            Open a project to review its full history and contact the company or customer.
        </div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>{{ request('stalled') ? 'Stalled-project follow-up' : 'All projects' }}</h2>
                <p>Sorted by most recent activity. Select a project to review its milestones and complete update history.</p>
            </div>

            <form method="GET" action="{{ route('admin.projects.index') }}" class="table-filters log-filters">
                @if (request('stalled'))
                    <input type="hidden" name="stalled" value="1">
                @endif

                <select name="status" class="select-control" aria-label="Project status">
                    <option value="">All statuses</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="company_id" class="select-control" aria-label="Company">
                    <option value="">All companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) ($filters['company_id'] ?? '') === (string) $company->id)>
                            {{ $company->name }}{{ $company->deleted_at ? ' (deleted)' : '' }}
                        </option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="customer" value="{{ $filters['customer'] ?? '' }}"
                    placeholder="Customer name, email or ID" maxlength="100" aria-label="Filter by customer">

                <span class="log-date-range">
                    <input class="select-control" type="date" name="last_activity_from"
                        value="{{ $filters['last_activity_from'] ?? '' }}" aria-label="Activity from date">
                    <span aria-hidden="true">→</span>
                    <input class="select-control" type="date" name="last_activity_to"
                        value="{{ $filters['last_activity_to'] ?? '' }}" aria-label="Activity to date">
                </span>

                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Project, request or company" maxlength="100" aria-label="Search projects">

                <button type="submit" class="btn btn-light">Filter</button>
                @if (request()->hasAny($filterKeys))
                    <a href="{{ route('admin.projects.index', request('stalled') ? ['stalled' => 1] : []) }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Last activity</th>
                        <th>Follow-up</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        @php
                            $activityAt = $project->last_update_at
                                ? \Illuminate\Support\Carbon::parse($project->last_update_at)
                                : $project->updated_at;
                            $isStalled = in_array($project->status, ['accepted', 'in_progress'], true)
                                && $activityAt?->lt(now()->subDays($stalledDays));
                            $customerName = $project->customer?->full_name ?: $project->customer?->username ?: 'Customer';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.projects.show', ['id' => $project->id]) }}"><strong>Project #{{ $project->id }}</strong></a>
                                <small class="cell-note">Request #{{ $project->quote_id }} · {{ $project->quote?->quote_name ?: 'Solar installation' }}</small>
                            </td>
                            <td>
                                @if ($project->company && !$project->company->trashed())
                                    <a href="{{ route('admin.companies.details', $project->company_id) }}">{{ $project->company->name }}</a>
                                @else
                                    {{ $project->company?->name ?: 'Company #' . $project->company_id }}{{ $project->company?->trashed() ? ' (deleted)' : '' }}
                                @endif
                            </td>
                            <td>
                                <strong>{{ $customerName }}</strong>
                                <small class="cell-note">{{ $project->customer?->email ?: ($project->customer?->phone ?: 'Customer #' . $project->customer_id) }}</small>
                            </td>
                            <td><span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">{{ $statusLabels[$project->status] ?? ucfirst($project->status) }}</span></td>
                            <td style="min-width:125px">
                                <strong>{{ (int) $project->progress_percent }}%</strong>
                                <div class="mini-progress mini-progress-lg"><span style="width: {{ (int) $project->progress_percent }}%"></span></div>
                            </td>
                            <td style="white-space:nowrap">
                                {{ $activityAt?->format('M d, Y H:i') ?? '—' }}
                                <small class="cell-note">{{ $activityAt?->diffForHumans() ?? 'No activity recorded' }}</small>
                            </td>
                            <td>
                                @if ($isStalled)
                                    <span class="pill pill-red">{{ (int) $activityAt->diffInDays(now()) }} days idle</span>
                                    <small class="cell-note">Review and contact the company</small>
                                @elseif ($project->status === 'accepted')
                                    <span class="pill pill-orange">Waiting to start</span>
                                @elseif ($project->status === 'in_progress')
                                    <span class="pill pill-blue">In progress</span>
                                @else
                                    <span class="pill pill-green">Closed</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.projects.show', ['id' => $project->id]) }}" class="row-action row-action-icon"
                                    title="View project" aria-label="View project #{{ $project->id }}">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>No projects found.</strong>
                                @if (request()->hasAny($filterKeys)) Try adjusting or clearing the filters. @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $projects, 'label' => 'projects'])
    </section>
@endsection
