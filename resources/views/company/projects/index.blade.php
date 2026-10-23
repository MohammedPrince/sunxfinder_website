
@extends('company.layouts.app')

@section('title', 'Active Projects')
@section('breadcrumb', 'Active Projects')
@section('eyebrow', 'PROJECT MANAGEMENT')
@section('page-title', 'Active Projects')
@section('page-description', 'Installations for customers who accepted your offer. Keep each one moving and the customer informed.')

@section('content')
    @php
        $statusLabels = [
            'accepted' => 'Not started',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
        ];

        $statusClasses = [
            'accepted' => 'pill-orange',
            'in_progress' => 'pill-blue',
            'completed' => 'pill-green',
        ];
    @endphp

    @include('partials.page-guide', [
        'key' => 'company-projects',
        'title' => 'How projects work',
        'intro' => 'A project is created automatically when a customer accepts your offer.',
        'steps' => [
            '<strong>Start installation</strong> when your team is ready. Until then the project shows as "Not started".',
            '<strong>Update milestones</strong> as each phase is done. Progress is calculated for you.',
            '<strong>Confirm customer handover</strong> as the last step. The project then closes at 100%.',
        ],
        'note' => 'Every update you save (with its message) is shown to the customer in the app.',
    ])

    <div class="metrics-strip">
        <div>
            <small>Not started</small>
            <strong class="{{ $metrics['accepted'] ? 'warning' : '' }}">{{ number_format($metrics['accepted']) }}</strong>
        </div>
        <div>
            <small>In progress</small>
            <strong>{{ number_format($metrics['in_progress']) }}</strong>
        </div>
        <div>
            <small>Completed</small>
            <strong>{{ number_format($metrics['completed']) }}</strong>
        </div>
        <div>
            <small>Total projects</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Your projects</h2>
                <p>Most recently updated first. The "Next step" column tells you what to do.</p>
            </div>

            <form method="GET"
                  action="{{ route('company.projects.index') }}"
                  class="table-filters">

                <select name="status" class="select-control">
                    <option value="">All project statuses</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="search"
                    name="search"
                    class="table-search"
                    value="{{ request('search') }}"
                    maxlength="100"
                    placeholder="Customer, service or #ID"
                    aria-label="Search projects"
                >

                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('company.projects.index') }}" class="btn btn-light">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Customer</th>
                        <th>Quote name</th>
                        <th>Selected offer</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Last update</th>
                        <th>Next step</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($projects as $project)
                        @php
                            $customer = $project->customer;
                            $quote = $project->quote;
                            $offer = $project->companyQuotation;

                            $customerName = $customer?->full_name
                                ?: ($customer?->username ?: 'Customer #' . $project->customer_id);

                            $reference = '#Q-' . str_pad(
                                (string) $project->quote_id,
                                5,
                                '0',
                                STR_PAD_LEFT
                            );

                            // 99% means every installation phase is done and only the handover is left.
                            [$nextLabel, $nextClass] = match (true) {
                                $project->status === 'accepted' => ['Start installation', 'pill-orange'],
                                $project->status === 'in_progress' && $project->progress_percent >= 99 => ['Confirm handover', 'pill-green'],
                                $project->status === 'in_progress' => ['Update progress', 'pill-blue'],
                                default => ['View project', 'pill-green'],
                            };

                            $idleDays = (int) $project->updated_at?->diffInDays(now());
                        @endphp

                        <tr>
                            <td>
                                <strong>Project #{{ $project->id }}</strong>
                                <small class="cell-note">Request {{ $reference }}</small>
                            </td>

                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">
                                        {{ strtoupper(mb_substr($customerName, 0, 1)) }}
                                    </span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer?->city ?: ($customer?->phone ?? 'No contact details') }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>{{ $quote?->quote_name ?? 'Solar installation' }}</td>

                            <td>
                                @if ($offer)
                                    <strong>
                                        {{ $offer->currency }}
                                        {{ number_format((float) $offer->price, 2) }}
                                    </strong>
                                @else
                                    —
                                @endif
                            </td>

                            <td style="min-width:130px">
                                <strong>{{ $project->progress_percent }}%</strong>
                                <div class="mini-progress mini-progress-lg"
                                    role="progressbar" aria-valuenow="{{ $project->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                                    <span style="width: {{ $project->progress_percent }}%"></span>
                                </div>
                            </td>

                            <td>
                                <span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">
                                    {{ $statusLabels[$project->status] ?? ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>

                            <td>
                                {{ $project->updated_at?->format('d M Y') ?? '—' }}
                                @if ($project->status !== 'completed' && $idleDays >= \App\Support\ReportPeriod::STALLED_DAYS)
                                    <span class="pill pill-red" title="No update for {{ $idleDays }} days">{{ $idleDays }} days idle</span>
                                @else
                                    <small class="cell-note">{{ $project->updated_at?->diffForHumans() }}</small>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('company.projects.show', $project->id) }}" class="pill {{ $nextClass }} next-step-link">
                                    {{ $nextLabel }} →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>No projects found.</strong>
                                Projects appear here after a customer accepts one of your offers.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $projects, 'label' => 'projects'])
    </section>
@endsection
