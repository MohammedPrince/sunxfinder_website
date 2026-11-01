@extends('company.layouts.app')
@section('title', __('company/messages.dashboard.title'))
@section('breadcrumb', __('company/messages.dashboard.title'))
@section('eyebrow', __('company/messages.dashboard.eyebrow'))
@section('page-title', __('company/messages.dashboard.page_title'))
@section('page-description', __('company/messages.dashboard.page_description', ['company' => $company->name ?? __('company/messages.dashboard.your_company')]))
@section('page-actions')
    <a class="btn btn-light" href="{{ route('company.products.index') }}">{{ __('company/messages.dashboard.my_products') }}</a>
    <a class="btn btn-primary" href="{{ route('company.quote-requests.index') }}">{{ __('company/messages.dashboard.browse_requests') }}</a>
@endsection

@section('content')
    @php
        $requestStatusLabels = [
            'pending' => __('company/messages.common.pending'),
            'accepted' => __('company/messages.common.accepted'),
            'rejected' => __('company/messages.common.rejected'),
            'completed' => __('company/messages.common.completed'),
            'cancelled' => __('company/messages.common.cancelled'),
        ];

        $requestStatusClasses = [
            'pending' => 'pill-yellow',
            'accepted' => 'pill-orange',
            'rejected' => 'pill-red',
            'completed' => 'pill-green',
            'cancelled' => 'pill-purple',
        ];

        $offerStatusLabels = [
            'submitted' => __('company/messages.common.pending'),
            'selected' => __('company/messages.common.selected'),
            'not_selected' => __('company/messages.dashboard.status_not_selected'),
            'withdrawn' => __('company/messages.common.rejected'),
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
            <strong>{{ $company->status === 'rejected' ? __('company/messages.dashboard.not_approved_notice') : __('company/messages.dashboard.awaiting_notice') }}</strong>
            <span>{!! __('company/messages.dashboard.approval_body', ['link' => '<a href="' . route('company.profile') . '">' . e(__('company/messages.dashboard.company_profile')) . '</a>']) !!}</span>
        </div>
    @endif

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-top">
                <span>{{ __('company/messages.dashboard.awaiting_offer') }}</span>
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
                    <b class="warning">{{ __('company/messages.dashboard.needs_attention') }}</b>
                @else
                    <b class="positive">{{ __('company/messages.dashboard.all_caught_up') }}</b>
                @endif
                {{ __('company/messages.dashboard.open_requests', ['count' => number_format($pendingRequests)]) }}
            </div>
            <div class="stat-progress">
                <span style="width:{{ $percentOf($pendingRequests - $awaitingOffer, $pendingRequests) }}%"></span>
            </div>
            <small>{{ __('company/messages.dashboard.open_offered') }}</small>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>{{ __('company/messages.dashboard.offers_submitted') }}</span>
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
                    <b class="positive">{{ __('company/messages.dashboard.win_rate', ['rate' => $offers['win_rate']]) }}</b>
                @endif
                {{ __('company/messages.dashboard.selected_by_customers', ['count' => number_format($offers['selected'])]) }}
            </div>
            <div class="mini-bars" title="{{ __('company/messages.dashboard.offers_per_day') }}">
                @foreach ($activity as $day)
                    <i style="height:{{ max(8, $barHeight($day['offers'])) }}%"></i>
                @endforeach
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>{{ __('company/messages.dashboard.active_projects') }}</span>
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
                <b class="positive">{{ __('company/messages.dashboard.completed_count', ['count' => number_format($projects['completed'])]) }}</b>
                {{ __('company/messages.dashboard.in_progress_count', ['count' => number_format($projects['in_progress'])]) }}
            </div>
            <div class="stat-progress green-progress">
                <span style="width:{{ $projects['average_progress'] }}%"></span>
            </div>
            <small>{{ __('company/messages.dashboard.average_progress', ['percent' => $projects['average_progress']]) }}</small>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span>{{ __('company/messages.dashboard.products_listed') }}</span>
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
                <b class="positive">{{ __('company/messages.dashboard.active_count', ['count' => number_format($products['active'])]) }}</b>
                {{ __('company/messages.dashboard.hidden_count', ['count' => number_format($products['total'] - $products['active'])]) }}
            </div>
            <div class="stat-progress green-progress">
                <span style="width:{{ $percentOf($products['active'], $products['total']) }}%"></span>
            </div>
            <small>{{ __('company/messages.dashboard.visible_customers') }}</small>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.dashboard.your_activity') }}</h2>
                    <p>{{ __('company/messages.dashboard.activity_sub') }}</p>
                </div>
            </div>
            <div class="chart-legend">
                <span>{{ __('company/messages.dashboard.legend_requests') }}</span>
                <span class="orange-text">{{ __('company/messages.dashboard.legend_offers') }}</span>
            </div>
            <div class="simple-chart">
                @foreach ($activity as $day)
                    <div class="chart-col"
                        title="{{ __('company/messages.dashboard.chart_title', ['date' => $day['date'], 'requests' => $day['requests'], 'offers' => $day['offers']]) }}">
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
                    <h2>{{ __('company/messages.dashboard.offer_outcomes') }}</h2>
                    <p>{{ __('company/messages.dashboard.outcomes_sub') }}</p>
                </div>
            </div>
            <div class="donut-area">
                <div class="donut" style="background:{{ $donutBackground }}">
                    <div><strong>{{ number_format($offers['total']) }}</strong><small>{{ __('company/messages.dashboard.offers') }}</small></div>
                </div>
            </div>
            <div class="status-list">
                <div>
                    <span class="positive">{{ __('company/messages.dashboard.legend_selected') }}</span>
                    <strong>{{ number_format($offers['selected']) }} <small>{{ $selectedShare }}%</small></strong>
                </div>
                <div>
                    <span class="orange-text">{{ __('company/messages.dashboard.legend_awaiting') }}</span>
                    <strong>{{ number_format($offers['submitted']) }} <small>{{ $awaitingShare }}%</small></strong>
                </div>
                <div>
                    <span class="gray-text">{{ __('company/messages.dashboard.legend_closed') }}</span>
                    <strong>{{ number_format($offers['not_selected'] + $offers['withdrawn']) }}
                        <small>{{ $closedShare }}%</small></strong>
                </div>
            </div>
            <a class="text-link" href="{{ route('company.projects.index') }}">{{ __('company/messages.dashboard.view_projects') }}</a>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.dashboard.latest_requests') }}</h2>
                <p>{{ __('company/messages.dashboard.latest_sub') }}</p>
            </div>
            <a class="text-link" href="{{ route('company.quote-requests.index') }}">{{ __('company/messages.dashboard.view_all') }}</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.request') }}</th>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.quote_name') }}</th>
                        <th>{{ __('company/messages.common.received') }}</th>
                        <th>{{ __('company/messages.common.your_offer') }}</th>
                        <th>{{ __('company/messages.dashboard.request_status') }}</th>
                        <th>{{ __('company/messages.common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestRequests as $quote)
                        @php
                            $customer = $quote->customer;

                            $customerName = $customer?->name
                                ?? $customer?->full_name
                                ?? $customer?->customer_name
                                ?? __('company/messages.common.customer_number', ['id' => $quote->customer_id]);

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
                                        <small>{{ $customer?->email ?? __('company/messages.dashboard.no_email') }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $quote->quote_name ?? __('company/messages.common.solar_installation') }}</td>
                            <td>{{ $quote->created_at?->translatedFormat('M d, Y') ?? '—' }}</td>
                            <td>
                                @if ($myOffer)
                                    <strong>{{ $myOffer->currency }} {{ number_format((float) $myOffer->price, 2) }}</strong>
                                    <span class="pill {{ $offerStatusClasses[$myOffer->status] ?? 'pill-blue' }}">
                                        {{ $offerStatusLabels[$myOffer->status] ?? ucfirst($myOffer->status) }}
                                    </span>
                                @else
                                    <span class="gray-text">{{ __('company/messages.dashboard.no_offer_yet') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill {{ $requestStatusClasses[$quote->status] ?? 'pill-blue' }}">
                                    {{ $requestStatusLabels[$quote->status] ?? ucfirst($quote->status) }}
                                </span>
                            </td>
                            <td>
                                <a class="row-action" href="{{ route('company.quote-requests.show', $quote->id) }}">
                                    {{ $canOffer ? __('company/messages.dashboard.send_offer') : __('company/messages.dashboard.view') }} ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>{{ __('company/messages.dashboard.empty_title') }}</strong>
                                {{ __('company/messages.dashboard.empty_body') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
