@extends('company.layouts.app')

@section('title', __('company/messages.navigation.quote_requests'))
@section('breadcrumb', __('company/messages.navigation.quote_requests'))
@section('eyebrow', __('company/messages.quote_requests.eyebrow'))
@section('page-title', __('company/messages.navigation.quote_requests'))
@section('page-description', __('company/messages.quote_requests.page_description'))

@section('content')
    @php
        $requestStatusLabels = [
            'pending' => __('company/messages.quote_requests.open'),
            'accepted' => __('company/messages.common.accepted'),
            'rejected' => __('company/messages.common.rejected'),
            'completed' => __('company/messages.common.completed'),
            'cancelled' => __('company/messages.common.cancelled'),
        ];

        $offerStatusLabels = [
            'submitted' => __('company/messages.quote_requests.waiting_customer'),
            'selected' => __('company/messages.common.won'),
            'not_selected' => __('company/messages.common.not_selected'),
            'withdrawn' => __('company/messages.common.not_selected'),
        ];

        $offerStatusClasses = [
            'submitted' => 'pill-yellow',
            'selected' => 'pill-green',
            'not_selected' => 'pill-red',
            'withdrawn' => 'pill-red',
        ];

        $authenticatedCompanyId = auth()->guard('company')->id();
    @endphp

    @include('partials.page-guide', [
        'key' => 'company-quote-requests',
        'title' => __('company/messages.quote_requests.guide_title'),
        'intro' => __('company/messages.quote_requests.guide_intro'),
        'steps' => [
            __('company/messages.quote_requests.guide_1'),
            __('company/messages.quote_requests.guide_2'),
            __('company/messages.quote_requests.guide_3'),
            __('company/messages.quote_requests.guide_4'),
        ],
        'note' => __('company/messages.quote_requests.guide_note'),
    ])

    <div class="metrics-strip">
        <div>
            <small>{{ __('company/messages.quote_requests.waiting_your_offer') }}</small>
            <strong class="{{ $metrics['awaiting_offer'] ? 'warning' : '' }}">{{ number_format($metrics['awaiting_offer']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.quote_requests.open_requests') }}</small>
            <strong>{{ number_format($metrics['pending']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.quote_requests.offers_won') }}</small>
            <strong>{{ number_format($metrics['won']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.quote_requests.all_visible') }}</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.quote_requests.customer_requests') }}</h2>
                <p>{{ __('company/messages.quote_requests.requests_sub') }}</p>
            </div>

            <form method="GET" action="{{ route('company.quote-requests.index') }}" class="table-filters">

                <select name="status" class="select-control" aria-label="{{ __('company/messages.quote_requests.request_status') }}">
                    <option value="">{{ __('company/messages.quote_requests.all_requests') }}</option>

                    @foreach ($requestStatusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('company/messages.quote_requests.search_placeholder') }}" maxlength="100" aria-label="{{ __('company/messages.quote_requests.search_label') }}">

                <button type="submit" class="btn btn-light">{{ __('company/messages.common.search') }}</button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('company.quote-requests.index') }}" class="btn btn-light">
                        {{ __('company/messages.common.clear') }}
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.request') }}</th>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.quote_name') }}</th>
                        <th>{{ __('company/messages.quote_requests.energy_needs') }}</th>
                        <th>{{ __('company/messages.common.received') }}</th>
                        <th>{{ __('company/messages.quote_requests.offers') }}</th>
                        <th>{{ __('company/messages.common.your_offer') }}</th>
                        <th>{{ __('company/messages.common.next_step') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotes as $quote)
                        @php
                            $customer = $quote->customer;

                            $customerName = $customer?->full_name
                                ?: ($customer?->username ?: __('company/messages.common.customer_number', ['id' => $quote->customer_id]));

                            // This relationship holds only this company's own offer.
                            $myOffer = $quote->companyQuotations->first();
                            $project = $quote->project; // only loaded when this company won
                            $offerStatus = $myOffer?->status;

                            // Another company was chosen: this offer is not selected, or the
                            // request now belongs to a different company.
                            $belongsToAnotherCompany = $quote->company_id !== null
                                && (string) $quote->company_id !== (string) $authenticatedCompanyId;

                            $lostToAnother = $offerStatus === 'not_selected'
                                || ($belongsToAnotherCompany
                                    && ($myOffer || in_array($quote->status, ['accepted', 'completed'], true)));

                            if ($lostToAnother && $myOffer && $offerStatus !== 'withdrawn') {
                                $offerStatus = 'not_selected';
                            }

                            $isDirect = (string) $quote->company_id === (string) $authenticatedCompanyId && !$project;

                            // What the company should do next on this request.
                            [$nextLabel, $nextClass, $nextUrl] = match (true) {
                                $project !== null && $project->status === 'accepted' => [__('company/messages.quote_requests.start_project'), 'pill-green', route('company.projects.show', $project->id)],
                                $project !== null && $project->status === 'in_progress' => [__('company/messages.quote_requests.update_progress_percent', ['percent' => $project->progress_percent]), 'pill-blue', route('company.projects.show', $project->id)],
                                $project !== null => [__('company/messages.quote_requests.project_completed'), 'pill-green', route('company.projects.show', $project->id)],
                                $lostToAnother => [__('company/messages.quote_requests.closed_another'), 'pill-red', route('company.quote-requests.show', $quote->id)],
                                in_array($quote->status, ['cancelled', 'rejected'], true) => [__('company/messages.quote_requests.closed_customer'), 'pill-purple', route('company.quote-requests.show', $quote->id)],
                                $quote->status === 'pending' && !$myOffer => [__('company/messages.quote_requests.send_your_offer'), 'pill-orange', route('company.quote-requests.show', $quote->id)],
                                $quote->status === 'pending' && $offerStatus === 'submitted' => [__('company/messages.quote_requests.customer_deciding'), 'pill-yellow', route('company.quote-requests.show', $quote->id)],
                                default => [__('company/messages.quote_requests.view_request'), 'pill-blue', route('company.quote-requests.show', $quote->id)],
                            };
                        @endphp

                        <tr>
                            <td>
                                <strong>#Q-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                <small class="cell-note">{{ $isDirect ? __('company/messages.quote_requests.sent_direct') : __('company/messages.quote_requests.open_all') }}</small>
                            </td>

                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer?->city ?: __('company/messages.common.city_not_set') }}</small>
                                    </span>
                                </div>
                            </td>

                            <td><strong>{{ $quote->quote_name }}</strong></td>

                            <td>
                                <strong>{{ $quote->daily_load ?: '—' }}</strong>
                                <small class="cell-note">{{ __('company/messages.quote_requests.peak', ['value' => $quote->peak_load ?: '—']) }}</small>
                            </td>

                            <td>
                                {{ $quote->created_at?->translatedFormat('d M Y') ?? '—' }}
                                <small class="cell-note">{{ $quote->created_at?->diffForHumans() }}</small>
                            </td>

                            <td>
                                <strong>{{ (int) $quote->total_offers_count }}</strong>
                                <small class="cell-note">{{ $myOffer ? __('company/messages.quote_requests.incl_yours') : __('company/messages.quote_requests.from_others') }}</small>
                            </td>

                            <td>
                                @if ($myOffer)
                                    <strong>{{ $myOffer->currency }} {{ number_format((float) $myOffer->price, 2) }}</strong>
                                    <span class="pill {{ $offerStatusClasses[$offerStatus] ?? 'pill-blue' }}">
                                        {{ $offerStatusLabels[$offerStatus] ?? ucfirst((string) $offerStatus) }}
                                    </span>
                                @else
                                    <span class="gray-text">{{ __('company/messages.quote_requests.not_sent') }}</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ $nextUrl }}" class="pill {{ $nextClass }} next-step-link">
                                    {{ $nextLabel }} {{ __('company/messages.common.arrow_next') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>{{ __('company/messages.quote_requests.empty_title') }}</strong>
                                {{ __('company/messages.quote_requests.empty_body') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $quotes, 'label' => 'requests'])
    </section>
@endsection
