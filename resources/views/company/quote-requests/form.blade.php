@extends('company.layouts.app')

@section('title', 'Quote Details')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'COMPANY ENQUIRIES')
@section('page-title', 'Quote details')
@section('page-description', 'Review the customer enquiry and requested solar system.')

@section('page-actions')
    <div class="quote-page-actions no-print">
        <a href="{{ route('company.quote-requests.index') }}" class="btn btn-light">
            &larr; Back to Requests
        </a>

        @if ($companyQuotation?->status === 'selected' && $project)
            <a href="{{ route('company.projects.show', $project->id) }}" class="btn btn-green-light">
                View Project &rarr;
            </a>
        @endif

        <button type="button" class="btn btn-primary" onclick="printQuote()">
            Print / Save PDF
        </button>
    </div>
@endsection

@section('content')
    @php
        $customer = $quote->customer;
        $company = $quote->company;

        $customerName = $customer?->name
            ?? $customer?->full_name
            ?? $customer?->customer_name
            ?? 'Customer #' . $quote->customer_id;

        $companyQuotation = $companyQuotation ?? null;
        $authenticatedCompanyId = auth()->guard('company')->id();

        $statusLabels = [
            'pending' => 'Pending',
            'submitted' => 'Offer Submitted',
            'selected' => 'Offer Selected',
            'not_selected' => 'Not Selected',
            'rejected' => 'Rejected',
            'accepted' => 'Accepted',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'withdrawn' => 'Not Selected',
            'selected_another' => 'Selected another offer',
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
            $status === 'selected' => 'The customer chose your offer. Manage the installation in the project.',
            $status === 'submitted' && $quote->status === 'pending' => 'Your offer was sent. The customer is comparing offers; you can still edit yours.',
            in_array($status, ['not_selected', 'withdrawn', 'selected_another'], true) => 'The customer chose another company. This request is closed for you.',
            $status === 'pending' => 'The customer is waiting for offers. Send yours below.',
            $status === 'cancelled' => 'The customer cancelled this request.',
            default => 'This request is no longer accepting offers.',
        };
    @endphp

    <div class="quote-print-page">
        <header class="quote-document-header print-only">
            <div class="quote-document-brand">
                {{ $company?->name ?? 'Solar Company' }}
            </div>

            <div class="quote-document-subtitle">
                Solar Energy Quote Request
            </div>

            <div class="quote-document-reference">
                Reference: <strong>{{ $reference }}</strong>
            </div>
        </header>

        @include('partials.page-guide', [
            'key' => 'company-quote-details',
            'title' => 'How to respond to this request',
            'intro' => 'A customer used the SunXFinder calculator and is asking for a total price for the system below.',
            'steps' => [
                '<strong>Check the energy needs</strong>: daily energy, peak load and the suggested panels, inverter and batteries.',
                '<strong>Compare prices</strong>: other companies’ prices appear without their names.',
                '<strong>Make an offer</strong>: one total price for the complete system, plus a message in English and Arabic.',
                '<strong>Wait for the customer</strong>: you can edit your offer until they choose. If they choose you, "View Project" appears at the top.',
            ],
            'note' => 'Your price and messages are shown to the customer in the app.',
        ])

        <section class="quote-summary">
            <div class="quote-summary-item">
                <span>Request reference</span>
                <strong>{{ $reference }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Status</span>

                <strong class="pill {{ $statusClasses[$status] ?? 'pill-blue' }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </strong>

                <small class="quote-status-note">{{ $statusExplanation }}</small>
            </div>
            <div class="quote-summary-item">
                <span>Daily energy</span>
                <strong>{{ $quote->daily_load ?: '—' }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Peak load</span>
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
                        <h3>Competitor Pricing</h3>
                        <p>Other companies have submitted offers for this request.</p>
                    </div>
                </div>

                <div class="competitor-pricing-summary">
                    <span>
                        {{ $competitorPrices->count() }}
                        {{ $competitorPrices->count() === 1 ? 'other offer' : 'other offers' }}
                        submitted
                    </span>
                    <span>Company identities are hidden</span>
                </div>

                @foreach ($competitorPrices->groupBy('currency') as $currency => $offers)
                    <div class="competitor-currency-group">
                        <div class="competitor-currency-title">
                            Prices in {{ $currency }}
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
                                    Offer {{ $index + 1 }}
                                </span>

                                <strong class="competitor-price-value">
                                    {{ $currency }}
                                    {{ number_format((float) $offer->price, 2) }}

                                    @if ($isLowest && $isHighest)
                                        <span class="price-arrow price-equal" title="All offers have the same price">
                                            ↕
                                        </span>
                                    @elseif ($isLowest)
                                        <span class="price-arrow price-down" title="Lowest price">
                                            ↓
                                        </span>
                                    @elseif ($isHighest)
                                        <span class="price-arrow price-up" title="Highest price">
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
                        Review the competing prices and submit your best total price
                        for the complete solar system. Focus on offering competitive
                        value without compromising quality.
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
                        <span class="offer-eyebrow" style="color:#172b45;">YOUR OFFER WON</span>
                        <h2>The customer chose you</h2>
                        <p style="font-size:14px;">Plan the installation and keep the customer updated from the project page.</p>
                    @elseif ($canEditOffer)
                        <span class="offer-eyebrow" style="color:#172b45;">OFFER SENT</span>
                        <h2>Customer is comparing offers</h2>
                        <p style="font-size:14px;">You can still improve your price or message until the customer decides.</p>
                    @elseif ($canSubmitOffer)
                        <span class="offer-eyebrow" style="color:#172b45;">COMPETE FOR THIS REQUEST</span>
                        <h2>Win the customer's business</h2>
                        <p style="font-size:14px;">
                            Send one total price for the complete system. Customers compare price, message and your
                            company profile.
                        </p>
                    @else
                        <span class="offer-eyebrow" style="color:#172b45;">REQUEST CLOSED</span>
                        <h2>No more offers on this request</h2>
                        <p style="font-size:14px;">{{ $statusExplanation }}</p>
                    @endif

                    @if ($companyQuotation)
                        <div class="offer-current-price">
                            {{ number_format((float) $companyQuotation->price, 2) }}
                            <span>{{ $companyQuotation->currency }}</span>
                        </div>

                        @if ($companyQuotation->message)
                            <p class="offer-current-message">
                                {{ $companyQuotation->message }}
                            </p>
                        @endif

                        <span class="offer-submitted-label">
                            <b>Offer status:</b>
                            {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $companyQuotation->status)) }}
                        </span>
                    @else
                        <span class="offer-helper">
                            Request {{ $reference }} · Price and currency required
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
                                Edit Your Offer
                            @else
                                Make an Offer
                            @endif
                        </button>

                    @elseif ($companyQuotation?->status === 'selected')
                        <span class="offer-locked-message">
                            <strong>Your offer was selected by the customer!</strong>



                            <center>
                                @if ($companyQuotation?->status === 'selected' && $project)
                                    <div class="selected-offer-actions">
                                        <a href="{{ route('company.projects.show', $project->id) }}" class="btn btn-primary">
                                            {{ match ($project->status) {
                                                'completed' => 'View Project',
                                                'in_progress' => 'Update Project Progress',
                                                default => "Let's Start the Project",
                                            } }} →
                                        </a>
                                    </div>
                                @endif
                            </center>

                        </span>

                    @elseif ($companyQuotation?->status === 'withdrawn')
                        <span class="offer-locked-message">
                            <strong>Your offer was not selected by the customer.</strong>
                        </span>

                    @elseif ($companyQuotation?->status === 'not_selected' || $acceptedByAnotherCompany)
                        <span class="offer-locked-message">
                            <strong>Another company's offer was selected by the customer.</strong>
                        </span>

                    @elseif ($quote->status !== 'pending')
                        <span class="offer-locked-message">
                            This request is no longer accepting offers.
                        </span>

                    @else
                        <span class="offer-locked-message">
                            Your offer is no longer editable.
                        </span>
                    @endif
                </div>

            </div>
        </section>

        @if ($canMakeOrEditOffer)
            {{-- Make an Offer modal --}}
            <div class="offer-modal-overlay no-print" id="offerModal" aria-hidden="true">

                <section class="offer-modal" role="dialog" aria-modal="true" aria-labelledby="offerModalTitle">

                    <div class="offer-modal-header">
                        <div>
                            <span class="offer-eyebrow">QUOTE {{ $reference }}</span>

                            <h2 id="offerModalTitle">
                                {{ $canEditOffer ? 'Edit Your Offer' : 'Make an Offer' }}
                            </h2>

                            <p>
                                {{ $canEditOffer
                ? 'Update your total price or message before the customer selects your offer.'
                : "Enter the total price for the customer's requested system." }}
                            </p>
                        </div>

                        <button type="button" class="offer-close-button" onclick="closeOfferModal()" aria-label="Close modal">
                            &times;
                        </button>
                    </div>

                    <form method="POST" action="{{ route('company.quote-requests.offer.store', $quote->id) }}"
                        id="companyOfferForm">
                        @csrf

                        <div class="offer-modal-body">
                            @if ($errors->any())
                                <div class="offer-form-errors">
                                    <strong>Please correct the following:</strong>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="offer-request-summary">
                                <div>
                                    <span>Daily energy</span>
                                    <strong>{{ $quote->daily_load ?: '—' }}</strong>
                                </div>
                                <div>
                                    <span>Peak load</span>
                                    <strong>{{ $quote->peak_load ?: '—' }}</strong>
                                </div>
                            </div>

                            <div class="offer-form-group">
                                <label for="offer_price">
                                    Your total price <span>*</span>
                                </label>

                                <div class="offer-price-input">
                                    <input type="number" id="offer_price" name="price" class="form-control" min="1000"
                                        step="0.01" placeholder="e.g. 1500000"
                                        value="{{ old('price', $companyQuotation?->price) }}" required>

                                    <select name="currency" aria-label="Currency" required>
                                        <option value="SDG" @selected(old('currency', $companyQuotation?->currency ?? 'SDG') === 'SDG')>
                                            SDG
                                        </option>

                                        <option value="USD" @selected(old('currency', $companyQuotation?->currency ?? 'SDG') === 'USD')>
                                            USD
                                        </option>
                                    </select>
                                </div>

                                <small>
                                    Total price for the complete solar system. Minimum: 1,000
                                    in the selected currency.
                                </small>
                            </div>

                            <div class="offer-form-group">
                                <label for="message">
                                    <span>*</span> Message to customer (English)
                                </label>

                                <textarea name="message" id="message" class="form-control" rows="5" required maxlength="2000"
                                    placeholder="Describe the proposed solar system, including panels, inverter, batteries, installation, warranty, delivery time, 
                                                                                                    and what is included in the total price.">{{ old('message', $companyQuotation?->message ?? 'Thank you for considering our company for your solar energy project. This offer includes the proposed solar equipment, 
                                                                                                    installation, system testing, and warranty as specified in the quotation. Please review the details and contact us if you have any questions.') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="ar_message"><span style="color:#dc2626">*</span> Message to customer (Arabic)</label>

                                <textarea name="ar_message" id="ar_message" class="form-control" rows="5" dir="rtl" lang="ar"
                                    required maxlength="2000"
                                    placeholder="اكتب تفاصيل عرض السعر، والألواح الشمسية،
                                                                                                     والعاكس، والبطاريات، والتركيب، والضمان، ومدة التنفيذ...">{{ old('ar_message', $companyQuotation?->ar_message ??'شكرًا لاختياركم شركتنا لتقديم عرض لنظام الطاقة الشمسية.
                                                                                                      يشمل هذا العرض المعدات المقترحة وأعمال التركيب واختبار النظام والضمان وفقًا للتفاصيل الموضحة في عرض السعر.
                                                                                                      يرجى مراجعة التفاصيل والتواصل معنا إذا كانت لديكم أي استفسارات.') }}</textarea>

                                @error('ar_message')
                                    <small class="field-error">{{ $message }}</small>
                                @enderror
                            </div>


                            <div class="offer-notice">
                                <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 11v5M12 8h.01" />
                                </svg>

                                <p>
                                    Your price will be visible to the customer. They can compare offers
                                    and select one company.
                                </p>
                            </div>

                            <div class="offer-price-warning">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3 2.8 20h18.4L12 3Z" />
                                    <path d="M12 9v4M12 16h.01" />
                                </svg>

                                <div>
                                    <strong>
                                        {{ $canEditOffer ? 'You can update your offer' : 'Review your offer carefully' }}
                                    </strong>

                                    <p>
                                        @if ($canEditOffer)
                                            You can change your price, currency, or message while the offer
                                            is submitted. Once the customer selects your offer, it cannot
                                            be edited.
                                        @else
                                            Please verify your total price before submitting.
                                            You can edit your offer while it remains submitted.
                                        @endif
                                    </p>
                                </div>
                            </div>


                        </div>

                        <div class="offer-modal-footer">
                            <button type="button" class="offer-cancel-button" onclick="closeOfferModal()">Cancel</button>

                            <button type="submit" class="offer-primary-button" id="submitOfferButton">
                                {{ $canEditOffer ? 'Save Changes' : 'Submit Offer' }}
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
                    <h2>Request information</h2>
                    <p>Details submitted by the customer</p>
                </div>

                <span class="pill {{ $statusClasses[$status] ?? 'pill-blue' }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </div>

            <div class="quote-detail-grid">
                <div class="quote-detail">
                    <span>Request reference</span>
                    <strong>{{ $reference }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Service needed</span>
                    <strong>{{ $quote->service_needed }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Company assignment</span>
                    <strong class="quote-company-name">
                        {{ $company?->name ?? 'No company assigned' }}
                    </strong>
                </div>

                <div class="quote-detail">
                    <span>Daily load</span>
                    <strong>{{ $quote->daily_load ?: '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Peak load</span>
                    <strong>{{ $quote->peak_load ?: '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Submitted</span>
                    <strong>{{ $quote->created_at?->format('d M Y, H:i') ?? '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Last updated</span>
                    <strong>{{ $quote->updated_at?->format('d M Y, H:i') ?? '—' }}</strong>
                </div>
            </div>

            <div class="quote-subsection">
                {{-- Estimated load summary --}}
                @php
                    $loadSummary = $quote->estimated_load ?? '';

                    $dailyEnergy = '—';
                    $peakLoad = '—';
                    $panelCount = '—';
                    $inverterSize = '—';
                    $batteryCount = '—';
                    $systemType = '—';

                    if (preg_match('/Estimated load:\s*([^,.\r\n]+(?:\.\d+)?\s*kWh\/day)/i', $loadSummary, $matches)) {
                        $dailyEnergy = trim($matches[1]);
                    }

                    if (preg_match('/peak\s*([^,\r\n]+?)(?=\.\s*Suggested system:|[\r\n]|$)/i', $loadSummary, $matches)) {
                        $peakLoad = trim($matches[1]);
                    }

                    if (preg_match('/Suggested system:\s*(\d+)\s*panels?/i', $loadSummary, $matches)) {
                        $panelCount = $matches[1];
                    }

                    if (preg_match('/Suggested system:.*?(\d+(?:\.\d+)?)\s*kW\s*inverter/i', $loadSummary, $matches)) {
                        $inverterSize = $matches[1] . ' kW';
                    }

                    if (preg_match('/Suggested system:.*?(\d+)\s*batter(?:y|ies)/i', $loadSummary, $matches)) {
                        $batteryCount = $matches[1];
                    }

                    if (preg_match('/System type:\s*([^\r\n]+)/i', $loadSummary, $matches)) {
                        $systemType = trim($matches[1]);
                    }
                @endphp

                <div class="quote-subsection">
                    <div class="quote-subsection-heading">
                        <h3>Estimated load summary</h3>
                        <p>Calculator information submitted with this request</p>
                    </div>

                    {{-- Energy requirements --}}
                    <div class="quote-energy-grid">
                        <div class="quote-energy-item">
                            <span>Daily energy</span>
                            <strong>{{ $dailyEnergy }}</strong>
                        </div>

                        <div class="quote-energy-item">
                            <span>Peak load</span>
                            <strong>{{ $peakLoad }}</strong>
                        </div>

                        <div class="quote-energy-item">
                            <span>System type</span>
                            <strong>{{ $systemType }}</strong>
                        </div>
                    </div>

                    {{-- Recommended equipment --}}
                    <div class="quote-equipment-section">
                        <h4>Suggested equipment</h4>

                        <div class="quote-equipment-grid">
                            <div class="quote-equipment-item">
                                <span class="quote-equipment-icon">☀</span>
                                <div>
                                    <small>Solar panels</small>
                                    <strong>{{ $panelCount }}
                                        {{ is_numeric($panelCount) && (int) $panelCount === 1 ? 'Panel' : (is_numeric($panelCount) ? 'Panels' : '') }}</strong>
                                </div>
                            </div>

                            <div class="quote-equipment-item">
                                <span class="quote-equipment-icon">ϟ</span>
                                <div>
                                    <small>Inverter</small>
                                    <strong>{{ $inverterSize }}</strong>
                                </div>
                            </div>

                            <div class="quote-equipment-item">
                                <span class="quote-equipment-icon">▣</span>
                                <div>
                                    <small>Batteries</small>
                                    <strong>{{ $batteryCount }}
                                        {{ is_numeric($batteryCount) && (int) $batteryCount === 1 ? 'Battery' : (is_numeric($batteryCount) ? 'Batteries' : '') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Original calculator text --}}
                    <div class="quote-text-content">{{ $loadSummary ?: 'No calculator summary provided.' }}</div>
                </div>

            </div>

            <div class="quote-subsection">
                <div class="quote-subsection-heading">
                    <h3>Customer message</h3>
                    <p>Additional instructions or contact preferences</p>
                </div>

                <div class="quote-text-content">{{ $quote->message ?: 'No additional message provided.' }}</div>
            </div>
        </section>

        {{-- Customer and company information --}}
        <div class="quote-parties-grid">
            <section class="panel quote-panel quote-party-panel">
                <div class="panel-heading quote-section-heading">
                    <div>
                        <h2>Customer information</h2>
                        <p>Customer account associated with this quote</p>
                    </div>
                </div>

                <div class="quote-detail-grid quote-party-details">

                    <div class="quote-detail">
                        <span>Full name</span>
                        <strong>{{ $customerName }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Email</span>
                        <strong>{{ $customer?->email ?? '—' }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Phone</span>
                        <strong>{{ $customer?->phone ?? '—' }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>City</span>
                        <strong>{{ $customer?->city ?? '—' }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Address</span>
                        <strong>{{ $customer?->address ?? '—' }}</strong>
                    </div>
                </div>
            </section>

            <section class="panel quote-panel quote-party-panel">
                <div class="panel-heading quote-section-heading">
                    <div>
                        <h2>Company information</h2>
                        <p>Company assigned to this request</p>
                    </div>
                </div>

                <div class="quote-company-card">
                    @if ($company)
                        <div class="quote-company-mark" aria-hidden="true">
                            {{ strtoupper(substr($company->name, 0, 1)) }}
                        </div>

                        <div class="quote-company-details">
                            <span>Assigned solar company</span>
                            <strong class="quote-company-name">{{ $company->name }}</strong>
                            {{-- <small>Company ID: {{ $company->id }}</small> --}}
                        </div>
                    @else
                        <div class="quote-company-details">
                            <strong>No company assigned</strong>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <footer class="quote-document-footer print-only">
            <span> {{ $company?->name ?? 'Solar Company' }}</span>
            <span>Request {{ $reference }}</span>

        </footer>
    </div>

    <script>

        function printQuote() {
            const originalTitle = document.title;

            document.title = @json(
                'Quote Details-' . $reference . '-' . ($company?->name ?? 'Solar Company')
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
                document.getElementById('offer_price')?.focus();
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

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeOfferModal();
            }
        });

        @if ($errors->any() && $canMakeOrEditOffer)
            document.addEventListener('DOMContentLoaded', function () {
                openOfferModal();
            });
        @endif

    </script>

@endsection