@extends('company.layouts.app')

@section('title', __('company/messages.quote.title'))
@section('breadcrumb', __('company/messages.navigation.quote_requests'))
@section('eyebrow', __('company/messages.quote_requests.eyebrow'))
@section('page-title', __('company/messages.quote.page_title'))
@section('page-description', __('company/messages.quote.page_description'))

@section('page-actions')
    <div class="quote-page-actions no-print">
        <a href="{{ route('company.quote-requests.index') }}" class="btn btn-light">
            {{ __('company/messages.quote.back') }}
        </a>

        @if ($companyQuotation?->status === 'selected' && $project)
            <a href="{{ route('company.projects.show', $project->id) }}" class="btn btn-green-light">
                {{ __('company/messages.quote.view_project_arrow') }}
            </a>
        @endif

        @if ($companyQuotation?->invoice)
            <a href="{{ route('company.quote-requests.invoice', $quote->id) }}" class="btn btn-light">
                {{ __('company/messages.quote.view_invoice') }}
            </a>

            <a href="{{ route('company.quote-requests.invoice.pdf', $quote->id) }}" class="btn btn-light">
                {{ __('company/messages.quote.download_invoice') }}
            </a>
        @endif

        <button type="button" class="btn btn-primary" onclick="printQuote()">
            {{ __('company/messages.quote.print') }}
        </button>
    </div>
@endsection

@section('content')
    @php
        $customer = $quote->customer;
        $authenticatedCompany = auth()->guard('company')->user();
        // Another company's details are never shown; only this company, once it is chosen.
        $company = (int) $quote->company_id === (int) $authenticatedCompany?->id ? $quote->company : null;
        $hideCustomerContact = $quote->hidesCustomerContactFrom((int) $authenticatedCompany?->id, $companyQuotation ?? null);

        $customerName = $customer?->name
            ?? $customer?->full_name
            ?? $customer?->customer_name
            ?? __('company/messages.common.customer_number', ['id' => $quote->customer_id]);

        $companyQuotation = $companyQuotation ?? null;
        $invoice = $companyQuotation?->invoice;
        // Equipment parsed from the calculator text: used by the offer form and the load summary.
        $loadSummary = $quote->estimated_load ?? '';

        $dailyEnergy = '—';
        $peakLoad = '—';
        $panelCount = '—';
        $inverterSize = '—';
        $batteryCount = '—';
        $systemType = '—';

        /*
        |--------------------------------------------------------------------------
        | Daily energy
        |--------------------------------------------------------------------------
        | English: Estimated load: 22.4 kWh/day
        | Arabic:  الحمل التقديري: 22.4 كيلوواط ساعة/يوم
        */
        if (
            preg_match(
                '/Estimated load:\s*([\d.]+)\s*kWh\/day/i',
                $loadSummary,
                $matches
            )
        ) {
            $dailyEnergy = $matches[1] . ' kWh/day';
        } elseif (
            preg_match(
                '/الحمل التقديري:\s*([\d.]+)\s*كيلوواط\s*ساعة\/يوم/u',
                $loadSummary,
                $matches
            )
        ) {
            $dailyEnergy = $matches[1] . ' كيلوواط ساعة/يوم';
        }

        /*
        |--------------------------------------------------------------------------
        | Peak load
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/peak\s*([\d.]+)\s*kW/i',
                $loadSummary,
                $matches
            )
        ) {
            $peakLoad = $matches[1] . ' kW';
        } elseif (
            preg_match(
                '/الحمل الأقصى\s*([\d.]+)\s*كيلوواط/u',
                $loadSummary,
                $matches
            )
        ) {
            $peakLoad = $matches[1] . ' كيلوواط';
        }

        /*
        |--------------------------------------------------------------------------
        | Solar panels
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/Suggested system:\s*(\d+)\s*panels?/i',
                $loadSummary,
                $matches
            )
        ) {
            $panelCount = $matches[1];
        } elseif (
            preg_match(
                '/المنظومة المقترحة:\s*(\d+)\s*ألواح/u',
                $loadSummary,
                $matches
            )
        ) {
            $panelCount = $matches[1];
        }

        /*
        |--------------------------------------------------------------------------
        | Inverter size
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/Suggested system:.*?([\d.]+)\s*kW\s*inverter/i',
                $loadSummary,
                $matches
            )
        ) {
            $inverterSize = $matches[1] . ' kW';
        } elseif (
            preg_match(
                '/المنظومة المقترحة:.*?محوّل\s*([\d.]+)\s*كيلوواط/u',
                $loadSummary,
                $matches
            )
        ) {
            $inverterSize = $matches[1] . ' كيلوواط';
        }

        /*
        |--------------------------------------------------------------------------
        | Batteries
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/Suggested system:.*?([\d.]+)\s*kWh\s*batter(?:y|ies)/i',
                $loadSummary,
                $matches
            )
        ) {
            $batteryCount = $matches[1] . ' kWh';
        } elseif (
            preg_match(
                '/\p{Arabic}+\s*([\d.]+)\s*kWh/u',
                $loadSummary,
                $matches
            )
        ) {
            $batteryCount = $matches[1] . ' kWh';
        } elseif (
            preg_match(
                '/([\d.]+)\s*kWh\s*\p{Arabic}+/u',
                $loadSummary,
                $matches
            )
        ) {
            $batteryCount = $matches[1] . ' kWh';
        } elseif (
            preg_match(
                '/Suggested system:.*?(\d+)\s*batter(?:y|ies)/i',
                $loadSummary,
                $matches
            )
        ) {
            $batteryCount = $matches[1];
        } elseif (
            preg_match(
                '/المنظومة المقترحة:.*?(\d+)\s*بطاريات/u',
                $loadSummary,
                $matches
            )
        ) {
            $batteryCount = $matches[1];
        }

        /*
        |--------------------------------------------------------------------------
        | System type
        |--------------------------------------------------------------------------
        */
        if (
            preg_match(
                '/System type:\s*([^\r\n]+)/i',
                $loadSummary,
                $matches
            )
        ) {
            $systemType = trim($matches[1]);
        } elseif (
            preg_match(
                '/نوع المنظومة:\s*([^\r\n،,.]+)/u',
                $loadSummary,
                $matches
            )
        ) {
            $systemType = trim($matches[1]);
        }

        // Renumbered so new rows never reuse an index after a validation error.
        $invoiceItems = array_values(old('invoice_items', $invoice?->items ?? []));
        if (!$invoiceItems && $companyQuotation?->price) {
            // An offer saved without an invoice: start from its price.
            $invoiceItems = [[
                'description' => __('company/messages.quote.default_item'),
                'quantity' => 1,
                'unit_price' => old('price', $companyQuotation->price),
            ]];
        } elseif (!$invoiceItems) {
            // New offer: the three main parts, with the calculator's counts when it sent them.
            $invoiceItems = [
                ['description' => __('company/messages.quote.sug_panels'), 'quantity' => is_numeric($panelCount) ? (int) $panelCount : 1, 'unit_price' => ''],
                ['description' => __('company/messages.quote.sug_inverter'), 'quantity' => 1, 'unit_price' => ''],
                ['description' => __('company/messages.quote.sug_batteries'), 'quantity' => is_numeric($batteryCount) ? (int) $batteryCount : 1, 'unit_price' => ''],
            ];
        }
        $panelText = is_numeric($panelCount)
            ? $panelCount . ' ' . ((int) $panelCount === 1 ? __('company/messages.quote.panel_unit') : __('company/messages.quote.panels_unit'))
            : $panelCount;
        $batteryText = is_numeric($batteryCount)
            ? $batteryCount . ' ' . ((int) $batteryCount === 1 ? __('company/messages.quote.battery_unit') : __('company/messages.quote.sug_batteries'))
            : $batteryCount;
        $authenticatedCompanyId = auth()->guard('company')->id();

        $statusLabels = [
            'pending' => __('company/messages.quote.status_pending'),
            'submitted' => __('company/messages.quote.offer_submitted'),
            'selected' => __('company/messages.quote.offer_selected'),
            'not_selected' => __('company/messages.quote.status_not_selected'),
            'rejected' => __('company/messages.common.rejected'),
            'accepted' => __('company/messages.common.accepted'),
            'completed' => __('company/messages.common.completed'),
            'cancelled' => __('company/messages.common.cancelled'),
            'withdrawn' => __('company/messages.quote.status_not_selected'),
            'selected_another' => __('company/messages.quote.selected_another'),
        ];

        $statusClasses = [
            'pending' => 'pill-yellow',
            'submitted' => 'pill-blue',
            'selected' => 'pill-green',
            'not_selected' => 'pill-red',
            'rejected' => 'pill-red',
            'accepted' => 'pill-orange',
            'completed' => 'pill-green',
            'cancelled' => 'pill-purple',
            'withdrawn' => 'pill-red',
            'selected_another' => 'pill-red',
        ];

        // Display this company's offer status when an offer exists.
        // Withdrawn and not-selected offers both show "Not Selected". Without
        // an offer, a request taken by another company shows "Selected another offer".
        $belongsToAnotherCompany = $quote->company_id !== null
            && (string) $quote->company_id !== (string) $authenticatedCompanyId;

        $acceptedByAnotherCompany = $companyQuotation?->status === 'not_selected'
            || ($belongsToAnotherCompany
                && ($companyQuotation || in_array($quote->status, ['accepted', 'completed'], true)));

        if ($companyQuotation?->status === 'withdrawn') {
            $status = 'withdrawn';
        } elseif ($acceptedByAnotherCompany) {
            $status = $companyQuotation ? 'not_selected' : 'selected_another';
        } else {
            $status = $companyQuotation?->status ?? $quote->status;
        }

        $reference = '#Q-' . str_pad($quote->id, 5, '0', STR_PAD_LEFT);

        // SunXFinder commission: admin and company only. Never printed, shared or on the invoice.
        // The admin can set this offer's commission as its own % or a fixed amount instead of the company rate.
        $commissionType = $companyQuotation?->commission_type ?: 'company';
        $commissionRate = $commissionType === 'percent'
            ? (float) $companyQuotation->commission_rate
            : (float) ($authenticatedCompany?->commission_rate ?? 0);
        $commissionFixed = $commissionType === 'fixed' ? (float) $companyQuotation->commission_amount : null;
        $commissionRateLabel = rtrim(rtrim(number_format($commissionRate, 2, '.', ''), '0'), '.');
        $commissionHeading = $commissionType === 'fixed'
            ? __('company/messages.quote.commission_fixed_label')
            : __('company/messages.quote.commission_label', ['rate' => $commissionRateLabel]);

        // New offers can be submitted only while the request is pending.
        // Existing offers can be edited only while this company's offer is submitted.
        $canEditOffer = $quote->status === 'pending'
            && $companyQuotation?->status === 'submitted'
            && !$acceptedByAnotherCompany;

        $canSubmitOffer = $quote->status === 'pending'
            && !$companyQuotation;

        $canMakeOrEditOffer = $canSubmitOffer || $canEditOffer;

        // Offers the customer has: competitors still in the running plus this company's own.
        $offersCount = (isset($competitorPrices) ? $competitorPrices->count() : 0) + ($companyQuotation ? 1 : 0);

        // One plain sentence explaining the status above.
        $statusExplanation = match (true) {
            $status === 'selected' => __('company/messages.quote.explain_selected'),
            $status === 'submitted' && $quote->status === 'pending' => __('company/messages.quote.explain_submitted'),
            in_array($status, ['not_selected', 'withdrawn', 'selected_another'], true) => __('company/messages.quote.explain_another'),
            $status === 'pending' => __('company/messages.quote.explain_waiting'),
            $status === 'cancelled' => __('company/messages.quote.explain_cancelled'),
            default => __('company/messages.quote.no_longer_accepting'),
        };
    @endphp

    <div class="quote-print-page">
        <header class="quote-document-header print-only">
            <div class="quote-document-logo">
                <img src="{{ asset('visitors/images/sunxfinder-logo-email.png') }}" alt="SunXFinder" width="110" height="88">
                <p>{{ __('company/messages.invoices.brand_find') }} <span>{{ __('company/messages.invoices.brand_compare') }}</span> {{ __('company/messages.invoices.brand_connect') }}</p>
            </div>

            <div class="quote-document-brand">
                {{ $authenticatedCompany?->name ?? __('company/messages.quote.solar_company') }}
            </div>

            <div class="quote-document-subtitle">
                {{ __('company/messages.quote.doc_subtitle') }}
            </div>

            <div class="quote-document-reference">
                {{ __('company/messages.quote.doc_reference') }} <strong>{{ $reference }}</strong>
            </div>
        </header>

        @include('partials.page-guide', [
            'key' => 'company-quote-details',
            'title' => __('company/messages.quote.guide_title'),
            'intro' => __('company/messages.quote.guide_intro'),
            'steps' => [
                __('company/messages.quote.guide_1'),
                __('company/messages.quote.guide_2'),
                __('company/messages.quote.guide_3'),
                __('company/messages.quote.guide_4'),
            ],
            'note' => __('company/messages.quote.guide_note'),
        ])

        <section class="quote-summary">
            <div class="quote-summary-item">
                <span>{{ __('company/messages.common.reference') }}</span>
                <strong>{{ $reference }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>{{ __('company/messages.quote.status') }}</span>

                <strong class="pill {{ $statusClasses[$status] ?? 'pill-blue' }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </strong>

                <small class="quote-status-note">{{ $statusExplanation }}</small>
            </div>
            <div class="quote-summary-item">
                <span>{{ __('company/messages.quote.daily_energy') }}</span>
                <strong>{{ $quote->daily_load ?: '—' }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>{{ __('company/messages.quote.peak_load') }}</span>
                <strong>{{ $quote->peak_load ?: '—' }}</strong>
            </div>
        </section>

        @include('partials.request-journey', [
            'quote' => $quote,
            'offersCount' => $offersCount,
            'project' => $project ?? null,
        ])


        {{-- Competitor pricing: prices only, never show company identities --}}

        @if (isset($competitorPrices) && $competitorPrices->isNotEmpty())
            <section class="competitor-pricing-panel no-print">
                <div class="competitor-pricing-heading">
                    <div class="competitor-pricing-icon">
                        <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 3v18h18" />
                            <path d="m7 14 4-4 3 3 6-7" />
                            <path d="M15 6h5v5" />
                        </svg>
                    </div>

                    <div>
                        <h3>{{ __('company/messages.quote.competitor_pricing') }}</h3>
                        <p>{{ __('company/messages.quote.competitor_sub') }}</p>
                    </div>
                </div>

                <div class="competitor-pricing-summary">
                    <span>
                        {{ trans_choice('company/messages.quote.other_offers', $competitorPrices->count()) }}
                    </span>
                    <span>{{ __('company/messages.quote.identities_hidden') }}</span>
                </div>

                @foreach ($competitorPrices->groupBy('currency') as $currency => $offers)
                    <div class="competitor-currency-group">
                        <div class="competitor-currency-title">
                            {{ __('company/messages.quote.prices_in', ['currency' => $currency]) }}
                        </div>

                        @php
                            $sortedOffers = $offers->sortBy('price')->values();
                            $lowestPrice = $sortedOffers->min('price');
                            $highestPrice = $sortedOffers->max('price');
                        @endphp

                        @foreach ($sortedOffers as $index => $offer)
                            @php
                                $isLowest = (float) $offer->price === (float) $lowestPrice;
                                $isHighest = (float) $offer->price === (float) $highestPrice;
                            @endphp

                            <div class="competitor-price-row">
                                <span class="competitor-offer-label">
                                    {{ __('company/messages.quote.offer_n', ['number' => $index + 1]) }}
                                </span>

                                <strong class="competitor-price-value">
                                    {{ $currency }}
                                    {{ number_format((float) $offer->price, 2) }}

                                    @if ($isLowest && $isHighest)
                                        <span class="price-arrow price-equal" title="{{ __('company/messages.quote.same_price') }}">
                                            ↕
                                        </span>
                                    @elseif ($isLowest)
                                        <span class="price-arrow price-down" title="{{ __('company/messages.quote.lowest_price') }}">
                                            ↓
                                        </span>
                                    @elseif ($isHighest)
                                        <span class="price-arrow price-up" title="{{ __('company/messages.quote.highest_price') }}">
                                            ↑
                                        </span>
                                    @endif
                                </strong>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <div class="competitor-pricing-tip">
                    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 11v5M12 8h.01" />
                    </svg>

                    <p>
                        {{ __('company/messages.quote.competitor_tip') }}
                    </p>
                </div>
            </section>
        @endif


        {{-- Company offer section --}}
        <section class="panel offer-panel no-print">
            <div class="offer-panel-content">
                <div class="offer-icon">
                    <svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20" />
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>



                <div class="offer-panel-copy">
                    @if ($companyQuotation?->status === 'selected')
                        <span class="offer-eyebrow" style="color:#172b45;">{{ __('company/messages.quote.your_offer_won') }}</span>
                        <h2>{{ __('company/messages.quote.chose_you') }}</h2>
                        <p style="font-size:14px;">{{ __('company/messages.quote.plan_installation') }}
                        </p>
                    @elseif ($canEditOffer)
                        <span class="offer-eyebrow" style="color:#172b45;">{{ __('company/messages.quote.offer_sent') }}</span>
                        <h2>{{ __('company/messages.quote.comparing') }}</h2>
                        <p style="font-size:14px;">{{ __('company/messages.quote.can_improve') }}</p>
                    @elseif ($canSubmitOffer)
                        <span class="offer-eyebrow" style="color:#172b45;">{{ __('company/messages.quote.compete') }}</span>
                        <h2>{{ __('company/messages.quote.win_business') }}</h2>
                        <p style="font-size:14px;">
                            {{ __('company/messages.quote.send_total') }}
                        </p>
                    @else
                        <span class="offer-eyebrow" style="color:#172b45;">{{ __('company/messages.quote.request_closed') }}</span>
                        <h2>{{ __('company/messages.quote.no_more_offers') }}</h2>
                        <p style="font-size:14px;">{{ $statusExplanation }}</p>
                    @endif

                    @if ($companyQuotation)
                        <div class="offer-current-price">
                            {{ number_format((float) $companyQuotation->price, 2) }}
                            <span>{{ $companyQuotation->currency }}</span>
                        </div>

                        <p class="offer-commission-note">
                            {{ $companyQuotation->commission_type === 'fixed'
                                ? __('company/messages.quote.commission_fixed_label')
                                : __('company/messages.quote.commission_label', ['rate' => $companyQuotation->commissionRateLabel()]) }}:
                            <strong>{{ number_format((float) $companyQuotation->commission_amount, 2) }} {{ $companyQuotation->currency }}</strong>
                            @if ($companyQuotation->status !== 'selected')
                                <small>{{ __('company/messages.quote.commission_when_accepted') }}</small>
                            @endif
                        </p>

                        @if ($companyQuotation->message)
                            <p class="offer-current-message">
                                {{ $companyQuotation->message }}
                            </p>
                        @endif

                        <span class="offer-submitted-label">
                            <b>{{ __('company/messages.quote.offer_status') }}</b>
                            {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $companyQuotation->status)) }}
                        </span>
                    @else
                        <span class="offer-helper">
                            {{ __('company/messages.quote.offer_helper', ['reference' => $reference]) }}
                        </span>
                    @endif
                </div>


                <div class="offer-panel-action">
                    @if ($canMakeOrEditOffer)
                        <button type="button" class="offer-primary-button" onclick="openOfferModal()">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                @if ($canEditOffer)
                                    <path d="m16 4 4 4L9 19H5v-4L16 4Z" />
                                @else
                                    <path d="M12 5v14M5 12h14" />
                                @endif
                            </svg>

                            @if ($canEditOffer)
                                {{ __('company/messages.quote.edit_offer') }}
                            @else
                                {{ __('company/messages.quote.make_offer') }}
                            @endif
                        </button>

                    @elseif ($companyQuotation?->status === 'selected')
                        <span class="offer-locked-message">
                            <strong>{{ __('company/messages.quote.your_offer_selected') }}</strong>



                            <center>
                                @if ($companyQuotation?->status === 'selected' && $project)
                                                    <div class="selected-offer-actions">
                                                        <a href="{{ route('company.projects.show', $project->id) }}" class="btn btn-primary">
                                                            {{ match ($project->status) {
                                        'completed' => __('company/messages.quote.view_project'),
                                        'in_progress' => __('company/messages.quote.update_project'),
                                        default => __('company/messages.quote.start_project'),
                                    } }} →
                                                        </a>
                                                    </div>
                                @endif
                            </center>

                        </span>

                    @elseif ($companyQuotation?->status === 'withdrawn')
                        <span class="offer-locked-message">
                            <strong>{{ __('company/messages.quote.offer_not_selected') }}</strong>
                        </span>

                    @elseif ($companyQuotation?->status === 'not_selected' || $acceptedByAnotherCompany)
                        <span class="offer-locked-message">
                            <strong>{{ __('company/messages.quote.another_selected') }}</strong>
                        </span>

                    @elseif ($quote->status !== 'pending')
                        <span class="offer-locked-message">
                            {{ __('company/messages.quote.no_longer_accepting') }}
                        </span>

                    @else
                        <span class="offer-locked-message">
                            {{ __('company/messages.quote.not_editable') }}
                        </span>
                    @endif
                </div>

            </div>
        </section>

        @if ($canMakeOrEditOffer)
            {{-- Make an Offer modal --}}
            <div class="offer-modal-overlay no-print" id="offerModal" aria-hidden="true">

                <section class="offer-modal offer-modal-with-invoice" role="dialog" aria-modal="true" aria-labelledby="offerModalTitle">

                    <div class="offer-modal-header">
                        <div>
                            <span class="offer-eyebrow">{{ __('company/messages.quote.modal_eyebrow', ['reference' => $reference]) }}</span>

                            <h2 id="offerModalTitle">
                                {{ $canEditOffer ? __('company/messages.quote.edit_offer') : __('company/messages.quote.make_offer') }}
                            </h2>

                            <p>
                                {{ $canEditOffer
                                    ? __('company/messages.quote.modal_edit_sub')
                                    : __('company/messages.quote.modal_new_sub') }}
                            </p>
                        </div>

                        <button type="button" class="offer-close-button" onclick="closeOfferModal()" aria-label="{{ __('company/messages.quote.close_modal') }}">
                            &times;
                        </button>
                    </div>

                    <form method="POST" action="{{ route('company.quote-requests.offer.store', $quote->id) }}"
                        id="companyOfferForm">
                        @csrf

                        <div class="offer-modal-body">
                            @if ($errors->any())
                                <div class="offer-form-errors">
                                    <strong>{{ __('company/messages.quote.correct_following') }}</strong>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="offer-modal-main">
                                @include('partials.page-guide', [
                                    'key' => 'company-offer-modal',
                                    'title' => __('company/messages.quote.modal_guide_title'),
                                    'intro' => __('company/messages.quote.modal_guide_intro'),
                                    'steps' => [
                                        __('company/messages.quote.modal_guide_1'),
                                        __('company/messages.quote.modal_guide_2'),
                                        __('company/messages.quote.modal_guide_3'),
                                        __('company/messages.quote.modal_guide_4'),
                                        __('company/messages.quote.modal_guide_5'),
                                        __('company/messages.quote.modal_guide_6'),
                                        __('company/messages.quote.modal_guide_7', ['button' => $canEditOffer ? __('company/messages.common.save_changes') : __('company/messages.quote.submit_offer')]),
                                    ],
                                    'note' => __('company/messages.quote.modal_guide_note'),
                                ])

                                <div class="offer-form-group">
                                    <label for="offer_price">
                                        {{ __('company/messages.quote.total_price') }} <span>*</span>
                                        <em class="offer-price-source" id="offerPriceSource" hidden>{{ __('company/messages.quote.calculated_invoice') }}</em>
                                    </label>

                                    <div class="offer-price-input">
                                        <input type="number" id="offer_price" name="price" class="form-control" min="1000"
                                            step="0.01" placeholder="{{ __('company/messages.quote.price_placeholder') }}"
                                            value="{{ old('price', $companyQuotation?->price) }}" required>

                                        <select name="currency" aria-label="{{ __('company/messages.quote.currency') }}" required>
                                            <option value="SDG" @selected(old('currency', $companyQuotation?->currency ?? 'SDG') === 'SDG')>
                                                SDG
                                            </option>

                                            <option value="USD" @selected(old('currency', $companyQuotation?->currency ?? 'SDG') === 'USD')>
                                                USD
                                            </option>
                                        </select>
                                    </div>

                                    <small>
                                        {{ __('company/messages.quote.price_hint') }}
                                    </small>
                                    @error('price')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <section class="offer-invoice-card" aria-labelledby="offerInvoiceTitle">
                                    <div class="offer-invoice-heading">
                                        <div>
                                            <h3 id="offerInvoiceTitle">{{ __('company/messages.quote.invoice_heading') }}</h3>
                                            <p>{{ __('company/messages.quote.invoice_sub') }}</p>
                                        </div>
                                        <label class="offer-invoice-switch">
                                            <input type="hidden" name="include_invoice" value="0">
                                            <input type="checkbox" name="include_invoice" value="1" id="include_invoice"
                                                data-had-invoice="{{ $invoice ? '1' : '0' }}"
                                                @checked(old('include_invoice', $companyQuotation ? (bool) $invoice : true))>
                                            <span>{{ __('company/messages.quote.create_invoice') }}</span>
                                        </label>
                                    </div>

                                    <small class="offer-invoice-removal" id="invoiceRemovalNote" hidden>
                                        {{ __('company/messages.quote.invoice_removal') }}
                                    </small>

                                    <div id="invoiceBuilder" class="offer-invoice-content">
                                        <div class="offer-invoice-meta">
                                            <div class="offer-form-group">
                                                <label for="invoice_number">{{ __('company/messages.quote.invoice_number') }} <span>*</span></label>
                                                <input class="form-control" id="invoice_number" name="invoice_number" type="text" maxlength="60"
                                                    value="{{ old('invoice_number', $invoice?->invoice_number ?? $invoiceNumber) }}" required>
                                                @error('invoice_number')
                                                    <small class="field-error">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="offer-form-group">
                                                <label for="invoice_date">{{ __('company/messages.quote.invoice_date') }} <span>*</span></label>
                                                <input class="form-control" id="invoice_date" name="invoice_date" type="date"
                                                    value="{{ old('invoice_date', $invoice && $invoice->invoice_date ? \Illuminate\Support\Carbon::parse($invoice->invoice_date)->toDateString() : now()->toDateString()) }}" required>
                                            </div>
                                        </div>

                                        <div class="offer-invoice-parties">
                                            <div><span>{{ __('company/messages.quote.bill_to') }}</span><strong>{{ $customerName }}</strong>@unless ($hideCustomerContact)<small>{{ $customer?->email }}</small><small>{{ $customer?->phone }}</small>@endunless</div>
                                            <div><span>{{ __('company/messages.quote.ship_to') }}</span><strong>{{ $customer?->full_name ?: $customerName }}</strong>@unless ($hideCustomerContact)<small>{{ $customer?->address ?: $customer?->city }}</small>@endunless</div>
                                        </div>

                                        <div>
                                            <div class="offer-invoice-items-head">
                                                <div><strong>{{ __('company/messages.quote.items_services') }}</strong><span>{{ __('company/messages.quote.items_sub') }}</span></div>
                                                <button type="button" class="btn btn-light" id="addInvoiceItem">{{ __('company/messages.quote.add_item') }}</button>
                                            </div>
                                            <div class="offer-invoice-items" id="invoiceItems">
                                                @foreach ($invoiceItems as $item)
                                                    @include('company.quote-requests.partials.offer-invoice-item', ['index' => $loop->index, 'item' => $item])
                                                @endforeach
                                            </div>
                                            <div class="offer-invoice-suggestions" aria-label="{{ __('company/messages.quote.quick_add_label') }}">
                                                <span>{{ __('company/messages.quote.quick_add') }}</span>
                                                @foreach ([__('company/messages.quote.sug_panels'), __('company/messages.quote.sug_inverter'), __('company/messages.quote.sug_batteries'), __('company/messages.quote.sug_mounting'), __('company/messages.quote.sug_cables'), __('company/messages.quote.sug_installation')] as $suggestion)
                                                    <button type="button" data-invoice-suggestion="{{ $suggestion }}">{{ $suggestion }}</button>
                                                @endforeach
                                            </div>
                                            <template id="invoiceItemTemplate">
                                                @include('company.quote-requests.partials.offer-invoice-item', ['index' => '__INDEX__', 'item' => []])
                                            </template>
                                            @error('invoice_items')
                                                <small class="field-error">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="offer-invoice-adjustments">
                                            <div class="offer-form-group"><label for="invoice_discount">{{ __('company/messages.quote.discount') }}</label><input class="form-control" id="invoice_discount" name="invoice_discount" type="number" min="0" step="0.01" value="{{ old('invoice_discount', $invoice?->discount ?? 0) }}"></div>
                                            <div class="offer-form-group"><label for="invoice_tax_rate">{{ __('company/messages.quote.tax_rate') }}</label><input class="form-control" id="invoice_tax_rate" name="invoice_tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('invoice_tax_rate', $invoice?->tax_rate ?? 0) }}"></div>
                                            <div class="offer-form-group"><label for="invoice_shipping">{{ __('company/messages.quote.shipping') }}</label><input class="form-control" id="invoice_shipping" name="invoice_shipping" type="number" min="0" step="0.01" value="{{ old('invoice_shipping', $invoice?->shipping ?? 0) }}"></div>
                                        </div>

                                        <div class="offer-form-group">
                                            <label for="invoice_notes">{{ __('company/messages.quote.payment_notes') }}</label>
                                            <textarea class="form-control" id="invoice_notes" name="invoice_notes" rows="3" maxlength="2000" placeholder="{{ __('company/messages.quote.notes_placeholder') }}">{{ old('invoice_notes', $invoice?->payment_instructions) }}</textarea>
                                        </div>
                                    </div>
                                </section>

                                <div class="offer-messages">
                                    <div class="offer-form-group">
                                        <label for="message">
                                            <span>*</span> {{ __('company/messages.quote.message_en') }}
                                        </label>

                                        <textarea name="message" id="message" class="form-control" rows="5" required maxlength="2000"
                                            placeholder="{{ __('company/messages.quote.message_placeholder') }}">{{ old('message', $companyQuotation?->message ?? 'Thank you for considering our company for your solar energy project. This offer includes the proposed solar equipment, installation, system testing, and warranty as specified in the quotation. Please review the details and contact us if you have any questions.') }}</textarea>
                                        @error('message')
                                            <small class="field-error">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="offer-form-group">
                                        <label for="ar_message">
                                            <span>*</span> {{ __('company/messages.quote.message_ar') }}
                                        </label>

                                        <textarea name="ar_message" id="ar_message" class="form-control" rows="5" dir="rtl" lang="ar"
                                            required maxlength="2000"
                                            placeholder="اكتب تفاصيل عرض السعر، والألواح الشمسية، والمحول، والبطاريات، والتركيب، والضمان، ومدة التنفيذ...">{{ old('ar_message', $companyQuotation?->ar_message ?? 'شكرًا لاختياركم شركتنا لتقديم عرض لنظام الطاقة الشمسية. يشمل هذا العرض المعدات المقترحة وأعمال التركيب واختبار النظام والضمان وفقًا للتفاصيل الموضحة في عرض السعر. يرجى مراجعة التفاصيل والتواصل معنا إذا كانت لديكم أي استفسارات.') }}</textarea>
                                        @error('ar_message')
                                            <small class="field-error">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <aside class="offer-modal-aside">
                                <div class="offer-summary-card">
                                    <h3>{{ __('company/messages.quote.customer_needs') }}</h3>
                                    <dl>
                                        <div><dt>{{ __('company/messages.common.customer') }}</dt><dd>{{ $customerName }}</dd></div>
                                        <div><dt>{{ __('company/messages.quote.daily_energy') }}</dt><dd>{{ $quote->daily_load ?: '—' }}</dd></div>
                                        <div><dt>{{ __('company/messages.quote.peak_load') }}</dt><dd>{{ $quote->peak_load ?: '—' }}</dd></div>
                                        <div><dt>{{ __('company/messages.quote.sug_panels') }}</dt><dd>{{ $panelText }}</dd></div>
                                        <div><dt>{{ __('company/messages.quote.sug_inverter') }}</dt><dd>{{ $inverterSize }}</dd></div>
                                        <div><dt>{{ __('company/messages.quote.sug_batteries') }}</dt><dd>{{ $batteryText }}</dd></div>
                                        @foreach (($competitorPrices ?? collect())->groupBy('currency') as $competitorCurrency => $competitorOffers)
                                            <div data-competitor-currency="{{ $competitorCurrency }}">
                                                <dt>{{ __('company/messages.quote.lowest_other') }}</dt>
                                                <dd>{{ number_format((float) $competitorOffers->min('price'), 2) }} {{ $competitorCurrency }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                    @if (filled($quote->message))
                                        <div class="offer-customer-message">
                                            <span>{{ __('company/messages.quote.customer_message') }}</span>
                                            <p>{{ $quote->message }}</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="offer-summary-card">
                                    <h3>{{ __('company/messages.quote.offer_summary') }}</h3>
                                    <dl id="offerSummaryLines">
                                        <div><dt>{{ __('company/messages.quote.subtotal') }}</dt><dd id="summarySubtotal">0.00</dd></div>
                                        <div><dt>{{ __('company/messages.quote.discount') }}</dt><dd id="summaryDiscount">0.00</dd></div>
                                        <div><dt>{{ __('company/messages.quote.tax') }}</dt><dd id="summaryTax">0.00</dd></div>
                                        <div><dt>{{ __('company/messages.quote.shipping') }}</dt><dd id="summaryShipping">0.00</dd></div>
                                    </dl>
                                    <div class="offer-summary-total">
                                        <span>{{ __('company/messages.quote.offer_total') }}</span>
                                        <strong><span id="summaryTotal">0.00</span> <small data-offer-currency>{{ old('currency', $companyQuotation?->currency ?? 'SDG') }}</small></strong>
                                    </div>
                                    <small class="offer-summary-minimum" id="offerMinimumWarning" hidden>
                                        {{ __('company/messages.quote.minimum_warning') }}
                                    </small>
                                </div>

                                {{-- SunXFinder commission: shown to the company only, not to the customer --}}
                                <div class="offer-summary-card offer-commission-card" id="offerCommission"
                                    data-commission-rate="{{ $commissionRate }}"
                                    @if ($commissionFixed !== null) data-commission-fixed="{{ $commissionFixed }}" @endif>
                                    <h3>{{ $commissionHeading }}</h3>
                                    @if ($commissionType !== 'company')
                                        <small>{{ __('company/messages.quote.commission_set_for_offer') }}</small>
                                    @endif
                                    <div class="offer-summary-total">
                                        <span>{{ __('company/messages.quote.commission_of_price') }}</span>
                                        <strong><span id="summaryCommission">0.00</span> <small data-offer-currency>{{ old('currency', $companyQuotation?->currency ?? 'SDG') }}</small></strong>
                                    </div>
                                    <small>{{ __('company/messages.quote.commission_hint') }}</small>
                                </div>

                                <div class="offer-notice">
                                    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 11v5M12 8h.01" />
                                    </svg>

                                    <p>
                                        {{ __('company/messages.quote.offer_notice') }}
                                    </p>
                                </div>
                            </aside>
                        </div>

                        <div class="offer-modal-footer">
                            <div class="offer-footer-total">
                                <span>{{ __('company/messages.quote.offer_total') }}</span>
                                <strong><span id="footerTotal">0.00</span> <small data-offer-currency>{{ old('currency', $companyQuotation?->currency ?? 'SDG') }}</small></strong>
                            </div>

                            <button type="button" class="offer-cancel-button" onclick="closeOfferModal()">{{ __('company/messages.common.cancel') }}</button>

                            <button type="submit" class="offer-primary-button" id="submitOfferButton">
                                {{ $canEditOffer ? __('company/messages.common.save_changes') : __('company/messages.quote.submit_offer') }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

        {{-- Request information --}}
        <section class="panel quote-panel">
            <div class="panel-heading quote-section-heading">
                <div>
                    <h2>{{ __('company/messages.quote.request_info') }}</h2>
                    <p>{{ __('company/messages.quote.request_info_sub') }}</p>
                </div>

                <span class="pill {{ $statusClasses[$status] ?? 'pill-blue' }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </div>

            <div class="quote-detail-grid">
                <div class="quote-detail">
                    <span>{{ __('company/messages.common.reference') }}</span>
                    <strong>{{ $reference }}</strong>
                </div>

                <div class="quote-detail">
                    <span>{{ __('company/messages.common.quote_name') }}</span>
                    <strong>{{ $quote->quote_name }}</strong>
                </div>

                <div class="quote-detail">
                    <span>{{ __('company/messages.quote.company_assignment') }}</span>
                    <strong class="quote-company-name">
                        {{ $company?->name ?? ($quote->company_id ? __('company/messages.quote.another_company') : __('company/messages.quote.no_company')) }}
                    </strong>
                </div>

                <div class="quote-detail">
                    <span>{{ __('company/messages.quote.daily_load') }}</span>
                    <strong>{{ $quote->daily_load ?: '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>{{ __('company/messages.quote.peak_load') }}</span>
                    <strong>{{ $quote->peak_load ?: '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>{{ __('company/messages.quote.submitted') }}</span>
                    <strong>{{ $quote->created_at?->translatedFormat('d M Y, H:i') ?? '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>{{ __('company/messages.quote.last_updated') }}</span>
                    <strong>{{ $quote->updated_at?->translatedFormat('d M Y, H:i') ?? '—' }}</strong>
                </div>
            </div>

            {{-- Devices and watts from the app calculator --}}
            @include('partials.quote-appliances', ['quote' => $quote])

            <div class="quote-subsection">
                {{-- Estimated load summary --}}
                <div class="quote-subsection">
                    <div class="quote-subsection-heading">
                        <h3>{{ __('company/messages.quote.load_summary') }}</h3>
                        <p>{{ __('company/messages.quote.load_summary_sub') }}</p>
                    </div>

                    {{-- Energy requirements --}}
                    <div class="quote-energy-grid">
                        <div class="quote-energy-item">
                            <span>{{ __('company/messages.quote.daily_energy') }}</span>
                            <strong>{{ $dailyEnergy }}</strong>
                        </div>

                        <div class="quote-energy-item">
                            <span>{{ __('company/messages.quote.peak_load') }}</span>
                            <strong>{{ $peakLoad }}</strong>
                        </div>

                        <div class="quote-energy-item">
                            <span>{{ __('company/messages.quote.system_type') }}</span>
                            <strong>{{ $systemType }}</strong>
                        </div>
                    </div>

                    {{-- Recommended equipment --}}
                    <div class="quote-equipment-section">
                        <h4>{{ __('company/messages.quote.suggested_equipment') }}</h4>

                        <div class="quote-equipment-grid">
                            <div class="quote-equipment-item">
                                <span class="quote-equipment-icon">☀</span>
                                <div>
                                    <small>{{ __('company/messages.quote.sug_panels') }}</small>
                                    <strong>{{ $panelCount }}
                                        {{ is_numeric($panelCount) && (int) $panelCount === 1 ? __('company/messages.quote.panel_unit') : (is_numeric($panelCount) ? __('company/messages.quote.panels_unit') : '') }}</strong>
                                </div>
                            </div>

                            <div class="quote-equipment-item">
                                <span class="quote-equipment-icon">ϟ</span>
                                <div>
                                    <small>{{ __('company/messages.quote.sug_inverter') }}</small>
                                    <strong>{{ $inverterSize }}</strong>
                                </div>
                            </div>

                            <div class="quote-equipment-item">
                                <span class="quote-equipment-icon">▣</span>
                                <div>
                                    <small>{{ __('company/messages.quote.sug_batteries') }}</small>
                                    <strong>{{ $batteryCount }}
                                        {{ is_numeric($batteryCount) && (int) $batteryCount === 1 ? __('company/messages.quote.battery_unit') : (is_numeric($batteryCount) ? __('company/messages.quote.sug_batteries') : '') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Original calculator text --}}
                    <div class="quote-text-content">{{ $loadSummary ?: __('company/messages.quote.no_summary') }}</div>
                </div>

            </div>

            <div class="quote-subsection">
                <div class="quote-subsection-heading">
                    <h3>{{ __('company/messages.quote.customer_message') }}</h3>
                    <p>{{ __('company/messages.quote.customer_message_sub') }}</p>
                </div>

                <div class="quote-text-content">{{ $quote->message ?: __('company/messages.quote.no_message') }}</div>
            </div>
        </section>

        {{-- Customer and company information --}}
        <div class="quote-parties-grid">
            <section class="panel quote-panel quote-party-panel">
                <div class="panel-heading quote-section-heading">
                    <div>
                        <h2>{{ __('company/messages.quote.customer_info') }}</h2>
                        <p>{{ __('company/messages.quote.customer_info_sub') }}</p>
                    </div>
                </div>

                <div class="quote-detail-grid quote-party-details">

                    <div class="quote-detail">
                        <span>{{ __('company/messages.quote.full_name') }}</span>
                        <strong>{{ $customerName }}</strong>
                    </div>

                    @unless ($hideCustomerContact)
                        <div class="quote-detail">
                            <span>{{ __('company/messages.common.email') }}</span>
                            <strong>{{ $customer?->email ?? '—' }}</strong>
                        </div>

                        <div class="quote-detail">
                            <span>{{ __('company/messages.common.phone') }}</span>
                            <strong>{{ $customer?->phone ?? '—' }}</strong>
                        </div>

                        <div class="quote-detail">
                            <span>{{ __('company/messages.common.city') }}</span>
                            <strong>{{ $customer?->city ?? '—' }}</strong>
                        </div>

                        <div class="quote-detail">
                            <span>{{ __('company/messages.quote.address') }}</span>
                            <strong>{{ $customer?->address ?? '—' }}</strong>
                        </div>
                    @endunless
                </div>
            </section>

            <section class="panel quote-panel quote-party-panel">
                <div class="panel-heading quote-section-heading">
                    <div>
                        <h2>{{ __('company/messages.quote.company_info') }}</h2>
                        <p>{{ __('company/messages.quote.company_info_sub') }}</p>
                    </div>
                </div>

                <div class="quote-company-card">
                    @if ($company)
                        <div class="quote-company-mark" aria-hidden="true">
                            {{ name_initials($company->name) }}
                        </div>

                        <div class="quote-company-details">
                            <span>{{ __('company/messages.quote.assigned_company') }}</span>
                            <strong class="quote-company-name">{{ $company->name }}</strong>
                            {{-- <small>Company ID: {{ $company->id }}</small> --}}
                        </div>
                    @else
                        <div class="quote-company-details">
                            <strong>{{ $quote->company_id ? __('company/messages.quote.another_company') : __('company/messages.quote.no_company') }}</strong>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <footer class="quote-document-footer print-only">
            <span> {{ $authenticatedCompany?->name ?? __('company/messages.quote.solar_company') }}</span>
            <span>{{ __('company/messages.quote.footer_request', ['reference' => $reference]) }}</span>

        </footer>
    </div>

    <script>

        function printQuote() {
            const originalTitle = document.title;

            document.title = @json(
                __('company/messages.quote.print_title', ['reference' => $reference, 'company' => $authenticatedCompany?->name ?? __('company/messages.quote.solar_company')])
            );

            window.addEventListener('afterprint', function restoreTitle() {
                document.title = originalTitle;
            }, { once: true });

            window.print();
        }


        function openOfferModal() {
            const modal = document.getElementById('offerModal');
            if (!modal) return;

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            setTimeout(() => {
                const invoiceEnabled = document.getElementById('include_invoice')?.checked;
                const firstField = invoiceEnabled
                    ? document.querySelector('#invoiceItems input')
                    : document.getElementById('offer_price');

                firstField?.focus();
            }, 50);
        }

        function closeOfferModal() {
            const modal = document.getElementById('offerModal');
            if (!modal) return;

            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function closeOfferModalOnBackdrop(event) {
            if (event.target === event.currentTarget) {
                closeOfferModal();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const offerForm = document.getElementById('companyOfferForm');
            const invoiceToggle = document.getElementById('include_invoice');
            const invoiceBuilder = document.getElementById('invoiceBuilder');
            const invoiceItems = document.getElementById('invoiceItems');
            const invoiceItemTemplate = document.getElementById('invoiceItemTemplate');
            const addInvoiceItem = document.getElementById('addInvoiceItem');
            const offerPrice = document.getElementById('offer_price');
            const currency = document.querySelector('#companyOfferForm [name="currency"]');
            const submitButton = document.getElementById('submitOfferButton');

            if (!invoiceToggle || !invoiceBuilder || !invoiceItems) return;

            let nextInvoiceItemIndex = invoiceItems.querySelectorAll('[data-invoice-item]').length;

            const roundMoney = (amount) => Math.round((amount + Number.EPSILON) * 100) / 100;
            const formatMoney = (amount) => amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            const setText = (id, text) => {
                document.getElementById(id).textContent = text;
            };

            function updateRemoveButtons() {
                const rows = invoiceItems.querySelectorAll('[data-invoice-item]');
                rows.forEach(function (row) {
                    row.querySelector('[data-remove-invoice-item]').disabled = rows.length <= 1;
                });
            }

            function updateInvoiceState() {
                const enabled = invoiceToggle.checked;
                invoiceBuilder.hidden = !enabled;
                offerPrice.readOnly = enabled;
                document.getElementById('offerPriceSource').hidden = !enabled;
                document.getElementById('offerSummaryLines').hidden = !enabled;
                document.getElementById('invoiceRemovalNote').hidden = enabled || invoiceToggle.dataset.hadInvoice !== '1';
                invoiceBuilder.querySelectorAll('input, textarea, select, button').forEach(function (field) {
                    field.disabled = !enabled;
                });
                invoiceBuilder.querySelectorAll('[data-invoice-required]').forEach(function (field) {
                    field.required = enabled;
                });
                updateRemoveButtons();
                recalculateInvoice();
            }

            // Mirrors the totals in CompanyRepository::makeQuoteInvoiceData(), which has the final say.
            function recalculateInvoice() {
                let subtotal = 0;
                invoiceItems.querySelectorAll('[data-invoice-item]').forEach(function (row) {
                    const quantity = Math.max(0, Number(row.querySelector('[name$="[quantity]"]')?.value || 0));
                    const unitPrice = Math.max(0, Number(row.querySelector('[data-invoice-price]')?.value || 0));
                    const lineTotal = roundMoney(quantity * roundMoney(unitPrice));
                    subtotal += lineTotal;
                    row.querySelector('[data-line-total]').textContent = formatMoney(lineTotal);
                });
                subtotal = roundMoney(subtotal);

                const discount = Math.min(subtotal, Math.max(0, Number(document.getElementById('invoice_discount')?.value || 0)));
                const taxRate = Math.min(100, Math.max(0, Number(document.getElementById('invoice_tax_rate')?.value || 0)));
                const shipping = Math.max(0, Number(document.getElementById('invoice_shipping')?.value || 0));
                const tax = roundMoney((subtotal - discount) * taxRate / 100);
                const balance = roundMoney(subtotal - discount + tax + shipping);

                if (invoiceToggle.checked) offerPrice.value = balance.toFixed(2);

                const total = invoiceToggle.checked ? balance : Number(offerPrice.value || 0);
                const belowMinimum = total < 1000;

                setText('summarySubtotal', formatMoney(subtotal));
                setText('summaryDiscount', '− ' + formatMoney(discount));
                setText('summaryTax', formatMoney(tax));
                setText('summaryShipping', formatMoney(shipping));
                setText('summaryTotal', formatMoney(total));
                setText('footerTotal', formatMoney(total));
                // Mirrors CompanyRepository::submitQuoteOffer(), which saves the commission.
                const commissionData = document.getElementById('offerCommission')?.dataset || {};
                const commission = commissionData.commissionFixed !== undefined
                    ? Math.min(Number(commissionData.commissionFixed), total)
                    : roundMoney(total * Number(commissionData.commissionRate || 0) / 100);
                setText('summaryCommission', formatMoney(commission));
                document.getElementById('offerMinimumWarning').hidden = !belowMinimum;
                submitButton.disabled = belowMinimum;

                document.querySelectorAll('[data-offer-currency]').forEach(function (label) {
                    label.textContent = currency?.value || 'SDG';
                });
                document.querySelectorAll('[data-competitor-currency]').forEach(function (row) {
                    row.hidden = row.dataset.competitorCurrency !== currency?.value;
                });
            }

            function addItemRow(description = '') {
                const index = nextInvoiceItemIndex++;
                const template = document.createElement('template');
                template.innerHTML = invoiceItemTemplate.innerHTML.replaceAll('__INDEX__', index);

                const row = template.content.firstElementChild;
                row.querySelector('[name$="[description]"]').value = description;
                invoiceItems.appendChild(row);
                updateInvoiceState();

                return row;
            }

            addInvoiceItem?.addEventListener('click', function () {
                addItemRow().querySelector('input')?.focus();
            });

            invoiceBuilder.querySelectorAll('[data-invoice-suggestion]').forEach(function (button) {
                button.addEventListener('click', function () {
                    addItemRow(button.dataset.invoiceSuggestion).querySelector('[data-invoice-price]')?.focus();
                });
            });

            invoiceItems.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-remove-invoice-item]');
                if (!removeButton) return;
                const rows = invoiceItems.querySelectorAll('[data-invoice-item]');
                if (rows.length <= 1) return;
                removeButton.closest('[data-invoice-item]').remove();
                updateRemoveButtons();
                recalculateInvoice();
            });

            invoiceBuilder.addEventListener('input', recalculateInvoice);
            offerPrice.addEventListener('input', recalculateInvoice);
            currency?.addEventListener('change', recalculateInvoice);
            invoiceToggle.addEventListener('change', updateInvoiceState);

            // Stops a double click from sending the offer twice.
            offerForm.addEventListener('submit', function () {
                submitButton.disabled = true;
                submitButton.textContent = @json(__('company/messages.quote.saving'));
            });

            updateInvoiceState();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeOfferModal();
            }
        });

        @if (($errors->any() || request()->boolean('edit_invoice')) && $canMakeOrEditOffer)
            document.addEventListener('DOMContentLoaded', function () {
                openOfferModal();
            });
        @endif

    </script>

@endsection
