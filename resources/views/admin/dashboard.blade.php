@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('eyebrow', 'SUNXFINDER MARKETPLACE')
@section('page-title', 'Dashboard overview')
@section('page-description', 'Here is what is happening across your solar marketplace today.')
@section('page-actions')
    <a class="btn btn-light" href="{{ route('admin.quote-requests.index') }}">Quote requests</a>
    <a class="btn btn-primary" href="{{ route('admin.companies.index') }}">＋ Manage companies</a>
@endsection

@section('content')
    @php
        $statusClasses = [
            'approved' => 'pill-green',
            'pending' => 'pill-yellow',
            'rejected' => 'pill-red',
        ];

        $activityMax = max(1, collect($activity)->max(fn($day) => max($day['requests'], $day['companies'])));
        $barHeight = fn(int $value) => $value > 0 ? max(4, (int) round($value / $activityMax * 100)) : 0;

        $percentOf = fn(int $part, int $total) => $total > 0 ? (int) round($part / $total * 100) : 0;

        // Company verification donut: approved, pending, then rejected / other.
        $approvedShare = $percentOf($companies['approved'], $companies['total']);
        $pendingShare = $percentOf($companies['pending'], $companies['total']);
        $otherShare = $companies['total'] > 0 ? 100 - $approvedShare - $pendingShare : 0;
        $donutBackground = $companies['total'] > 0
            ? "conic-gradient(#1da875 0 {$approvedShare}%, var(--orange) {$approvedShare}% " .
                ($approvedShare + $pendingShare) .
                '%, #e6ebf0 ' .
                ($approvedShare + $pendingShare) .
                '% 100%)'
            : '#e6ebf0';
    @endphp

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-top">
                <span>Total companies</span>
                <span class="stat-icon blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <path d="M9 21V7h6v14" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($companies['total']) }}</div>
            <div class="stat-foot">
                @include('admin.partials.dashboard-change', ['change' => $companies['change']])
                {{ number_format($companies['new_this_month']) }} new this month
            </div>
            <div class="mini-bars" title="New companies per day, last 7 days">
                @foreach ($activity as $day)
                    <i style="height:{{ max(8, $barHeight($day['companies'])) }}%"></i>
                @endforeach
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>Pending verification</span>
                <span class="stat-icon orange">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($companies['pending']) }}</div>
            <div class="stat-foot">
                @if ($companies['pending'] > 0)
                    <b class="warning">Needs attention</b>
                @else
                    <b class="positive">All reviewed</b>
                @endif
            </div>
            <div class="stat-progress">
                <span style="width:{{ $pendingShare }}%"></span>
            </div>
            <small>Companies awaiting review</small>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>Quote requests</span>
                <span class="stat-icon purple">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 7h11l-3-3" />
                        <path d="M17 17H6l3 3" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($quotes['total']) }}</div>
            <div class="stat-foot">
                @include('admin.partials.dashboard-change', ['change' => $quotes['change']])
                {{ number_format($quotes['pending']) }} pending
            </div>
            <div class="mini-bars purple-bars" title="Quote requests per day, last 7 days">
                @foreach ($activity as $day)
                    <i style="height:{{ max(8, $barHeight($day['requests'])) }}%"></i>
                @endforeach
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>Active customers</span>
                <span class="stat-icon green">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                    </svg>
                </span>
            </div>
            <div class="stat-value">{{ number_format($customers['active']) }}</div>
            <div class="stat-foot">
                @include('admin.partials.dashboard-change', ['change' => $customers['change']])
                of {{ number_format($customers['total']) }} accounts
            </div>
            <div class="stat-progress green-progress">
                <span style="width:{{ $percentOf($customers['active'], $customers['total']) }}%"></span>
            </div>
            <small>Active share of all customer accounts</small>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Marketplace activity</h2>
                    <p>New quote requests and companies, last 7 days</p>
                </div>
            </div>
            <div class="chart-legend">
                <span>● Quote requests</span>
                <span class="orange-text">● New companies</span>
            </div>
            <div class="simple-chart">
                @foreach ($activity as $day)
                    <div class="chart-col"
                        title="{{ $day['date'] }}: {{ $day['requests'] }} requests, {{ $day['companies'] }} companies">
                        <i style="height:{{ $barHeight($day['requests']) }}%"></i>
                        <b style="height:{{ $barHeight($day['companies']) }}%"></b>
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
                    <h2>Company verification</h2>
                    <p>Provider profile status</p>
                </div>
            </div>
            <div class="donut-area">
                <div class="donut" style="background:{{ $donutBackground }}">
                    <div><strong>{{ number_format($companies['total']) }}</strong><small>Companies</small></div>
                </div>
            </div>
            <div class="status-list">
                <div>
                    <span class="positive">● Approved</span>
                    <strong>{{ number_format($companies['approved']) }} <small>{{ $approvedShare }}%</small></strong>
                </div>
                <div>
                    <span class="orange-text">● Pending</span>
                    <strong>{{ number_format($companies['pending']) }} <small>{{ $pendingShare }}%</small></strong>
                </div>
                <div>
                    <span class="gray-text">● Rejected / other</span>
                    <strong>{{ number_format($companies['other']) }} <small>{{ $otherShare }}%</small></strong>
                </div>
            </div>
            <a class="text-link" href="{{ route('admin.companies.index') }}">Review companies →</a>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>Recently added companies</h2>
                <p>Latest providers joining SunXFinder</p>
            </div>
            <a class="text-link" href="{{ route('admin.companies.index') }}">View all →</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Verification</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestCompanies as $company)
                        <tr>
                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar solar">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $company->name }}</strong>
                                        <small>SF-{{ str_pad((string) $company->id, 5, '0', STR_PAD_LEFT) }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $company->city ?: '—' }}</td>
                            <td>{{ $company->created_at?->format('M d, Y') ?? '—' }}</td>
                            <td>
                                <span class="pill {{ $statusClasses[$company->status] ?? 'pill-yellow' }}">
                                    {{ ucfirst($company->status) }}
                                </span>
                                @if (!$company->is_active)
                                    <small class="gray-text">Inactive</small>
                                @endif
                            </td>
                            <td>
                                @if ($company->is_verified)
                                    <span class="verify verified">✓ Verified</span>
                                @else
                                    <span class="verify pending">◷ Unverified</span>
                                @endif
                            </td>
                            <td>
                                <a class="row-action" href="{{ route('admin.companies.edit', $company->id) }}">
                                    {{ $company->status === 'pending' ? 'Review' : 'View' }} ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">
                                <strong>No companies yet.</strong>
                                New providers will appear here as soon as they register.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
