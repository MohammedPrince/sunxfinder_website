@extends('company.layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('eyebrow', 'COMPANY WORKSPACE')
@section('page-title', 'Dashboard overview')
@section('page-description', 'Here is what is happening with ' . ($company->name ?? 'your company') . ' today.')
@section('page-actions')
    <a class="btn btn-light" href="{{ route('company.products.index') }}">My products</a>
    <a class="btn btn-primary" href="{{ route('company.quote-requests.index') }}">Browse quote requests</a>
@endsection

@section('content')
    @php
        $requestStatusLabels = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $requestStatusClasses = [
            'pending' => 'pill-yellow',
            'accepted' => 'pill-orange',
            'rejected' => 'pill-red',
            'completed' => 'pill-green',
            'cancelled' => 'pill-purple',
        ];

        $offerStatusLabels = [
            'submitted' => 'Pending',
            'selected' => 'Selected',
            'not_selected' => 'Not Selected',
            'withdrawn' => 'Rejected',
        ];

        $offerStatusClasses = [
            'submitted' => 'pill-yellow',
            'selected' => 'pill-green',
            'not_selected' => 'pill-red',
            'withdrawn' => 'pill-red',
        ];

        $activityMax = max(1, collect($activity)->max(fn($day) => max($day['requests'], $day['offers'])));
        $barHeight = fn(int $value) => $value > 0 ? max(4, (int) round($value / $activityMax * 100)) : 0;

        $percentOf = fn(int $part, int $total) => $total > 0 ? (int) round($part / $total * 100) : 0;

        // Offer outcome donut: selected, awaiting decision, then not selected / withdrawn.
        $selectedShare = $percentOf($offers['selected'], $offers['total']);
        $awaitingShare = $percentOf($offers['submitted'], $offers['total']);
        $closedShare = $offers['total'] > 0 ? 100 - $selectedShare - $awaitingShare : 0;
        $donutBackground = $offers['total'] > 0
            ? "conic-gradient(#1da875 0 {$selectedShare}%, var(--orange) {$selectedShare}% " .
                ($selectedShare + $awaitingShare) .
                '%, #e6ebf0 ' .
                ($selectedShare + $awaitingShare) .
                '% 100%)'
            : '#e6ebf0';

        $authenticatedCompanyId = (string) ($company?->id ?? '');
    @endphp

    @if ($company && $company->status !== 'approved')
        <div class="dashboard-notice">
            <strong>Your company profile is {{ $company->status === 'rejected' ? 'not approved' : 'awaiting approval' }}.</strong>
            <span>Customers can find you on SunXFinder after an administrator approves your profile.
                Keep your <a href="{{ route('company.profile') }}">company profile</a> complete to speed this up.</span>
        </div>
    @endif

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-top">
                <span>Awaiting your offer</span>
                <span class="stat-icon orange">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($awaitingOffer) }}</div>
            <div class="stat-foot">
                @if ($awaitingOffer > 0)
                    <b class="warning">Needs attention</b>
                @else
                    <b class="positive">All caught up</b>
                @endif
                {{ number_format($pendingRequests) }} open requests
            </div>
            <div class="stat-progress">
                <span style="width:{{ $percentOf($pendingRequests - $awaitingOffer, $pendingRequests) }}%"></span>
            </div>
            <small>Open requests you have already offered on</small>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>Offers submitted</span>
                <span class="stat-icon purple">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 7h11l-3-3" />
                        <path d="M17 17H6l3 3" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($offers['total']) }}</div>
            <div class="stat-foot">
                @if ($offers['win_rate'] !== null)
                    <b class="positive">{{ $offers['win_rate'] }}% win rate</b>
                @endif
                {{ number_format($offers['selected']) }} selected by customers
            </div>
            <div class="mini-bars" title="Offers per day, last 7 days">
                @foreach ($activity as $day)
                    <i style="height:{{ max(8, $barHeight($day['offers'])) }}%"></i>
                @endforeach
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>Active projects</span>
                <span class="stat-icon blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($projects['active']) }}</div>
            <div class="stat-foot">
                <b class="positive">{{ number_format($projects['completed']) }} completed</b>
                {{ number_format($projects['in_progress']) }} in progress
            </div>
            <div class="stat-progress green-progress">
                <span style="width:{{ $projects['average_progress'] }}%"></span>
            </div>
            <small>{{ $projects['average_progress'] }}% average progress on active projects</small>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>Products listed</span>
                <span class="stat-icon green">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 8l-9-5-9 5 9 5 9-5z" />
                        <path d="M3 8v8l9 5 9-5V8" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($products['total']) }}</div>
            <div class="stat-foot">
                <b class="positive">{{ number_format($products['active']) }} active</b>
                {{ number_format($products['total'] - $products['active']) }} hidden
            </div>
            <div class="stat-progress green-progress">
                <span style="width:{{ $percentOf($products['active'], $products['total']) }}%"></span>
            </div>
            <small>Visible to customers on your profile</small>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Your activity</h2>
                    <p>New quote requests and offers you sent, last 7 days</p>
                </div>
            </div>
            <div class="chart-legend">
                <span>● Quote requests</span>
                <span class="orange-text">● Your offers</span>
            </div>
            <div class="simple-chart">
                @foreach ($activity as $day)
                    <div class="chart-col"
                        title="{{ $day['date'] }}: {{ $day['requests'] }} requests, {{ $day['offers'] }} offers">
                        <i style="height:{{ $barHeight($day['requests']) }}%"></i>
                        <b style="height:{{ $barHeight($day['offers']) }}%"></b>
                    </div>
                @endforeach
            </div>
            <div class="chart-days">
                @foreach ($activity as $day)
                    <span>{{ $day['label'] }}</span>
                @endforeach
            </div>
        </article>

        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Offer outcomes</h2>
                    <p>How customers responded to your offers</p>
                </div>
            </div>
            <div class="donut-area">
                <div class="donut" style="background:{{ $donutBackground }}">
                    <div><strong>{{ number_format($offers['total']) }}</strong><small>Offers</small></div>
                </div>
            </div>
            <div class="status-list">
                <div>
                    <span class="positive">● Selected</span>
                    <strong>{{ number_format($offers['selected']) }} <small>{{ $selectedShare }}%</small></strong>
                </div>
                <div>
                    <span class="orange-text">● Awaiting customer</span>
                    <strong>{{ number_format($offers['submitted']) }} <small>{{ $awaitingShare }}%</small></strong>
                </div>
                <div>
                    <span class="gray-text">● Not selected / withdrawn</span>
                    <strong>{{ number_format($offers['not_selected'] + $offers['withdrawn']) }}
                        <small>{{ $closedShare }}%</small></strong>
                </div>
            </div>
            <a class="text-link" href="{{ route('company.projects.index') }}">View projects →</a>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>Latest quote requests</h2>
                <p>Newest customer enquiries available to your company</p>
            </div>
            <a class="text-link" href="{{ route('company.quote-requests.index') }}">View all →</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Customer</th>
                        <th>Quote name</th>
                        <th>Received</th>
                        <th>Your offer</th>
                        <th>Request status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestRequests as $quote)
                        @php
                            $customer = $quote->customer;

                            $customerName = $customer?->name
                                ?? $customer?->full_name
                                ?? $customer?->customer_name
                                ?? 'Customer #' . $quote->customer_id;

                            $myOffer = $quote->companyQuotations->first();

                            $belongsToAnotherCompany = $quote->company_id !== null
                                && (string) $quote->company_id !== $authenticatedCompanyId;

                            $canOffer = $quote->status === 'pending' && !$myOffer && !$belongsToAnotherCompany;
                        @endphp
                        <tr>
                            <td><strong>#Q-{{ str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer?->email ?? 'No email' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $quote->quote_name ?? 'Solar installation' }}</td>
                            <td>{{ $quote->created_at?->format('M d, Y') ?? '—' }}</td>
                            <td>
                                @if ($myOffer)
                                    <strong>{{ $myOffer->currency }} {{ number_format((float) $myOffer->price, 2) }}</strong>
                                    <span class="pill {{ $offerStatusClasses[$myOffer->status] ?? 'pill-blue' }}">
                                        {{ $offerStatusLabels[$myOffer->status] ?? ucfirst($myOffer->status) }}
                                    </span>
                                @else
                                    <span class="gray-text">No offer yet</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill {{ $requestStatusClasses[$quote->status] ?? 'pill-blue' }}">
                                    {{ $requestStatusLabels[$quote->status] ?? ucfirst($quote->status) }}
                                </span>
                            </td>
                            <td>
                                <a class="row-action" href="{{ route('company.quote-requests.show', $quote->id) }}">
                                    {{ $canOffer ? 'Send offer' : 'View' }} ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>No quote requests yet.</strong>
                                New customer requests will appear here as soon as they arrive.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
