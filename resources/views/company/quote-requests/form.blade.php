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

        @if ($companyQuotation?->invoice)
            <a href="{{ route('company.quote-requests.invoice', $quote->id) }}" class="btn btn-light">
                View Invoice
            </a>

            <a href="{{ route('company.quote-requests.invoice.pdf', $quote->id) }}" class="btn btn-light">
                Download Invoice PDF
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
        $invoice = $companyQuotation?->invoice;
        // Renumbered so new rows never reuse an index after a validation error.
        $invoiceItems = array_values(old('invoice_items', $invoice?->items ?? []));
        if (!$invoiceItems) {
            $invoiceItems = [[
                'description' => 'Solar energy system',
                'quantity' => 1,
                'unit_price' => old('price', $companyQuotation?->price ?? ''),
            ]];
        }
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
            'note' => 'Your price, messages and invoice are shown to the customer in the app.',
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
                        <p style="font-size:14px;">Plan the installation and keep the customer updated from the project page.
                        </p>
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

                <section class="offer-modal offer-modal-with-invoice" role="dialog" aria-modal="true" aria-labelledby="offerModalTitle">

                    <div class="offer-modal-header">
                        <div>
                            <span class="offer-eyebrow">QUOTE {{ $reference }}</span>

                            <h2 id="offerModalTitle">
                                {{ $canEditOffer ? 'Edit Your Offer' : 'Make an Offer' }}
                            </h2>

                            <p>
                                {{ $canEditOffer
                                    ? 'Update your price, invoice or message before the customer decides.'
                                    : 'Build your price from an itemised invoice, or enter one total price.' }}
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

                            <div class="offer-modal-main">
                                @include('partials.page-guide', [
                                    'key' => 'company-offer-modal',
                                    'title' => 'How to submit your offer and invoice',
                                    'intro' => 'Your offer is one total price for the complete system. Build it from an invoice, or turn the invoice off and type the price yourself.',
                                    'steps' => [
                                        '<strong>Check the customer needs</strong>: the daily energy, peak load and lowest other offer are in the side panel.',
                                        '<strong>Choose the currency</strong>: SDG or USD. The same currency is used for the offer and the invoice.',
                                        '<strong>Fill in the invoice</strong>: check the invoice number and date. Each invoice number can be used only once by your company.',
                                        '<strong>Add items and services</strong>: enter a quantity and unit price for each one, or use Quick add. Then add any discount, tax and shipping.',
                                        '<strong>Check the offer total</strong>: it is calculated from the invoice, updates as you type, and must be at least 1,000.',
                                        '<strong>Write your message</strong>: explain what is included, in both English and Arabic.',
                                        '<strong>Press ' . ($canEditOffer ? 'Save Changes' : 'Submit Offer') . '</strong>: then use View Invoice or Download Invoice PDF at the top of this page.',
                                    ],
                                    'note' => 'The customer sees your price, messages and invoice in the app. You can edit them until the customer chooses an offer; after that they are locked.',
                                ])

                                <div class="offer-form-group">
                                    <label for="offer_price">
                                        Your total price <span>*</span>
                                        <em class="offer-price-source" id="offerPriceSource" hidden>Calculated from the invoice</em>
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
                                    @error('price')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <section class="offer-invoice-card" aria-labelledby="offerInvoiceTitle">
                                    <div class="offer-invoice-heading">
                                        <div>
                                            <h3 id="offerInvoiceTitle">Invoice for this offer</h3>
                                            <p>Saved with your offer. You can download it as a PDF, and the customer sees it in the app.</p>
                                        </div>
                                        <label class="offer-invoice-switch">
                                            <input type="hidden" name="include_invoice" value="0">
                                            <input type="checkbox" name="include_invoice" value="1" id="include_invoice"
                                                data-had-invoice="{{ $invoice ? '1' : '0' }}"
                                                @checked(old('include_invoice', $companyQuotation ? (bool) $invoice : true))>
                                            <span>Create invoice</span>
                                        </label>
                                    </div>

                                    <small class="offer-invoice-removal" id="invoiceRemovalNote" hidden>
                                        Saving will remove the invoice from this offer.
                                    </small>

                                    <div id="invoiceBuilder" class="offer-invoice-content">
                                        <div class="offer-invoice-meta">
                                            <div class="offer-form-group">
                                                <label for="invoice_number">Invoice number <span>*</span></label>
                                                <input class="form-control" id="invoice_number" name="invoice_number" type="text" maxlength="60"
                                                    value="{{ old('invoice_number', $invoice?->invoice_number ?? $invoiceNumber) }}" required>
                                                @error('invoice_number')
                                                    <small class="field-error">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="offer-form-group">
                                                <label for="invoice_date">Invoice date <span>*</span></label>
                                                <input class="form-control" id="invoice_date" name="invoice_date" type="date"
                                                    value="{{ old('invoice_date', $invoice && $invoice->invoice_date ? \Illuminate\Support\Carbon::parse($invoice->invoice_date)->toDateString() : now()->toDateString()) }}" required>
                                            </div>
                                        </div>

                                        <div class="offer-invoice-parties">
                                            <div><span>Bill to</span><strong>{{ $customerName }}</strong><small>{{ $customer?->email }}</small><small>{{ $customer?->phone }}</small></div>
                                            <div><span>Ship to</span><strong>{{ $customer?->full_name ?: $customerName }}</strong><small>{{ $customer?->address ?: $customer?->city }}</small></div>
                                        </div>

                                        <div>
                                            <div class="offer-invoice-items-head">
                                                <div><strong>Items and services</strong><span>List the equipment, installation, and other included work.</span></div>
                                                <button type="button" class="btn btn-light" id="addInvoiceItem">+ Add item</button>
                                            </div>
                                            <div class="offer-invoice-items" id="invoiceItems">
                                                @foreach ($invoiceItems as $item)
                                                    @include('company.quote-requests.partials.offer-invoice-item', ['index' => $loop->index, 'item' => $item])
                                                @endforeach
                                            </div>
                                            <div class="offer-invoice-suggestions" aria-label="Quick add items">
                                                <span>Quick add:</span>
                                                @foreach (['Solar panels', 'Inverter', 'Batteries', 'Mounting structure', 'Cables and protection', 'Installation and testing'] as $suggestion)
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
                                            <div class="offer-form-group"><label for="invoice_discount">Discount</label><input class="form-control" id="invoice_discount" name="invoice_discount" type="number" min="0" step="0.01" value="{{ old('invoice_discount', $invoice?->discount ?? 0) }}"></div>
                                            <div class="offer-form-group"><label for="invoice_tax_rate">Tax rate (%)</label><input class="form-control" id="invoice_tax_rate" name="invoice_tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('invoice_tax_rate', $invoice?->tax_rate ?? 0) }}"></div>
                                            <div class="offer-form-group"><label for="invoice_shipping">Shipping / handling</label><input class="form-control" id="invoice_shipping" name="invoice_shipping" type="number" min="0" step="0.01" value="{{ old('invoice_shipping', $invoice?->shipping ?? 0) }}"></div>
                                        </div>

                                        <div class="offer-form-group">
                                            <label for="invoice_notes">Payment instructions / remarks</label>
                                            <textarea class="form-control" id="invoice_notes" name="invoice_notes" rows="3" maxlength="2000" placeholder="Bank details, deposit, delivery notes...">{{ old('invoice_notes', $invoice?->payment_instructions) }}</textarea>
                                        </div>
                                    </div>
                                </section>

                                <div class="offer-messages">
                                    <div class="offer-form-group">
                                        <label for="message">
                                            <span>*</span> Message to customer (English)
                                        </label>

                                        <textarea name="message" id="message" class="form-control" rows="5" required maxlength="2000"
                                            placeholder="Describe the proposed solar system, including panels, inverter, batteries, installation, warranty, delivery time, and what is included in the total price.">{{ old('message', $companyQuotation?->message ?? 'Thank you for considering our company for your solar energy project. This offer includes the proposed solar equipment, installation, system testing, and warranty as specified in the quotation. Please review the details and contact us if you have any questions.') }}</textarea>
                                        @error('message')
                                            <small class="field-error">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="offer-form-group">
                                        <label for="ar_message">
                                            <span>*</span> Message to customer (Arabic)
                                        </label>

                                        <textarea name="ar_message" id="ar_message" class="form-control" rows="5" dir="rtl" lang="ar"
                                            required maxlength="2000"
                                            placeholder="اكتب تفاصيل عرض السعر، والألواح الشمسية، والعاكس، والبطاريات، والتركيب، والضمان، ومدة التنفيذ...">{{ old('ar_message', $companyQuotation?->ar_message ?? 'شكرًا لاختياركم شركتنا لتقديم عرض لنظام الطاقة الشمسية. يشمل هذا العرض المعدات المقترحة وأعمال التركيب واختبار النظام والضمان وفقًا للتفاصيل الموضحة في عرض السعر. يرجى مراجعة التفاصيل والتواصل معنا إذا كانت لديكم أي استفسارات.') }}</textarea>
                                        @error('ar_message')
                                            <small class="field-error">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <aside class="offer-modal-aside">
                                <div class="offer-summary-card">
                                    <h3>Customer needs</h3>
                                    <dl>
                                        <div><dt>Customer</dt><dd>{{ $customerName }}</dd></div>
                                        <div><dt>Daily energy</dt><dd>{{ $quote->daily_load ?: '—' }}</dd></div>
                                        <div><dt>Peak load</dt><dd>{{ $quote->peak_load ?: '—' }}</dd></div>
                                        @foreach (($competitorPrices ?? collect())->groupBy('currency') as $competitorCurrency => $competitorOffers)
                                            <div data-competitor-currency="{{ $competitorCurrency }}">
                                                <dt>Lowest other offer</dt>
                                                <dd>{{ number_format((float) $competitorOffers->min('price'), 2) }} {{ $competitorCurrency }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>

                                <div class="offer-summary-card">
                                    <h3>Offer summary</h3>
                                    <dl id="offerSummaryLines">
                                        <div><dt>Subtotal</dt><dd id="summarySubtotal">0.00</dd></div>
                                        <div><dt>Discount</dt><dd id="summaryDiscount">0.00</dd></div>
                                        <div><dt>Tax</dt><dd id="summaryTax">0.00</dd></div>
                                        <div><dt>Shipping / handling</dt><dd id="summaryShipping">0.00</dd></div>
                                    </dl>
                                    <div class="offer-summary-total">
                                        <span>Offer total</span>
                                        <strong><span id="summaryTotal">0.00</span> <small data-offer-currency>{{ old('currency', $companyQuotation?->currency ?? 'SDG') }}</small></strong>
                                    </div>
                                    <small class="offer-summary-minimum" id="offerMinimumWarning" hidden>
                                        The offer total must be at least 1,000.
                                    </small>
                                </div>

                                <div class="offer-notice">
                                    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 11v5M12 8h.01" />
                                    </svg>

                                    <p>
                                        The customer sees your price and compares offers. You can edit
                                        until they choose; after that your offer and invoice are locked.
                                    </p>
                                </div>
                            </aside>
                        </div>

                        <div class="offer-modal-footer">
                            <div class="offer-footer-total">
                                <span>Offer total</span>
                                <strong><span id="footerTotal">0.00</span> <small data-offer-currency>{{ old('currency', $companyQuotation?->currency ?? 'SDG') }}</small></strong>
                            </div>

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
                    <span>Quote name</span>
                    <strong>{{ $quote->quote_name }}</strong>
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

            {{-- Devices and watts from the app calculator --}}
            @include('partials.quote-appliances', ['quote' => $quote])

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
                submitButton.textContent = 'Saving...';
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
