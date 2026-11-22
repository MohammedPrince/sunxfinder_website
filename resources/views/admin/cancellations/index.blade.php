@extends('admin.layouts.app')

@section('title', 'Withdrawals')
@section('breadcrumb', 'Withdrawals')
@section('eyebrow', 'PROJECTS')
@section('page-title', 'Withdrawals from accepted jobs')
@section('page-description', 'Companies that backed out after a customer accepted their offer, and cancellation requests waiting for a decision.')

@section('content')
    @php
        $PC = \App\Models\ProjectCancellation::class;

        $statuses = [
            $PC::REQUESTED => 'Waiting for decision',
            $PC::WITHDRAWN => 'Withdrawn',
            $PC::APPROVED => 'Approved',
            $PC::REJECTED => 'Rejected',
            $PC::CANCELLED => 'Taken back',
        ];
        $statusClasses = [
            $PC::REQUESTED => 'pill-orange',
            $PC::WITHDRAWN => 'pill-red',
            $PC::APPROVED => 'pill-red',
            $PC::REJECTED => 'pill-blue',
            $PC::CANCELLED => 'pill-purple',
        ];
        $stages = ['accepted' => 'Not started', 'in_progress' => 'In progress', 'handover_pending' => 'Waiting for handover'];
        $filterKeys = ['search', 'status', 'company_id'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-cancellations',
        'title' => 'How withdrawals work',
        'intro' => 'A company can back out of a job the customer accepted. The customer keeps their request: it opens again, and the other companies\' offers can be chosen.',
        'steps' => [
            '<strong>Withdrawn</strong>: the job had not started, so the company withdrew by itself. Nothing to decide.',
            '<strong>Waiting for decision</strong>: work had started, or the company withdrew ' . $PC::FLAG_THRESHOLD . '+ times in ' . $PC::WINDOW_DAYS . ' days. Call both sides, then approve (the request reopens) or reject (the job carries on).',
            '<strong>Counted</strong>: a withdrawal counts against the company unless you waive it. At ' . $PC::FLAG_THRESHOLD . ' in ' . $PC::WINDOW_DAYS . ' days the company is flagged and listed lower in the app.',
            'When the customer declines a company\'s new price, it is recorded here too, and never counted.',
        ],
        'note' => 'Waive a withdrawal when the company was not at fault, for example when the customer really could not be reached.',
    ])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="metrics-strip">
        <div>
            <small>Waiting for decision</small>
            <strong class="{{ $metrics['requested'] ? 'warning' : '' }}">
                <a href="{{ route('admin.cancellations.index', ['status' => $PC::REQUESTED]) }}">{{ number_format($metrics['requested']) }}</a>
            </strong>
        </div>
        <div><small>Deals ended ({{ $PC::WINDOW_DAYS }} days)</small><strong>{{ number_format($metrics['done_recent']) }}</strong></div>
        <div><small>Offers reopened ({{ $PC::WINDOW_DAYS }} days)</small><strong>{{ number_format($metrics['reopened_recent']) }}</strong></div>
        <div><small>Flagged companies</small><strong class="{{ $metrics['flagged_companies'] ? 'warning' : '' }}">{{ number_format($metrics['flagged_companies']) }}</strong></div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>Withdrawals</h2>
                <p>{{ number_format($cancellations->total()) }} {{ \Illuminate\Support\Str::plural('record', $cancellations->total()) }}. Waiting requests first.</p>
            </div>

            <form method="GET" action="{{ route('admin.cancellations.index') }}" class="table-filters log-filters">
                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Company, customer, #request" maxlength="100" aria-label="Search withdrawals">

                <select class="select-control" name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                @if (!empty($filters['company_id']))
                    <input type="hidden" name="company_id" value="{{ $filters['company_id'] }}">
                @endif

                <button class="btn btn-light" type="submit">Filter</button>
                @if (request()->hasAny($filterKeys))
                    <a class="btn btn-light" href="{{ route('admin.cancellations.index') }}">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Reason</th>
                        <th>Stage</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cancellations as $cancellation)
                        <tr>
                            <td>
                                <a href="{{ route('admin.cancellations.show', $cancellation->id) }}">
                                    <strong>{{ \App\Support\OfferWithdrawal::reference((int) $cancellation->quote_id) }}</strong>
                                </a>
                                <small class="cell-note">{{ $cancellation->quote?->quote_name }}</small>
                            </td>
                            <td>
                                {{ $cancellation->company?->name ?? 'Company removed' }}
                            </td>
                            <td>{{ $cancellation->customer?->full_name ?: ($cancellation->customer?->username ?? '—') }}</td>
                            <td class="cell-wrap">
                                {{ $cancellation->reasonText('en') }}
                                @if ($cancellation->note)
                                    <small class="cell-note">{{ \Illuminate\Support\Str::limit($cancellation->note, 70) }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $stages[$cancellation->stage] ?? $cancellation->stage }}
                                <small class="cell-note">{{ $cancellation->progress_percent }}%</small>
                            </td>
                            <td>
                                <span class="pill {{ $statusClasses[$cancellation->status] ?? 'pill-blue' }}">{{ $statuses[$cancellation->status] ?? $cancellation->status }}</span>
                                @if ($cancellation->isDone())
                                    <small class="cell-note">
                                        {{ $cancellation->counts_against_company ? 'Counted' : 'Not counted' }}
                                        · {{ $cancellation->reopened_offers }} {{ \Illuminate\Support\Str::plural('offer', $cancellation->reopened_offers) }} reopened
                                    </small>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                {{ $cancellation->created_at?->format('M j, Y H:i') }}
                                <small class="cell-note">{{ $cancellation->created_at?->diffForHumans() }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.cancellations.show', $cancellation->id) }}" class="row-action row-action-icon"
                                    title="Open" aria-label="Open withdrawal on {{ \App\Support\OfferWithdrawal::reference((int) $cancellation->quote_id) }}">
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
                                <strong>No withdrawals found.</strong>
                                @if (request()->hasAny($filterKeys)) Try adjusting or clearing the filters. @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $cancellations, 'label' => 'withdrawals'])
    </section>
@endsection
