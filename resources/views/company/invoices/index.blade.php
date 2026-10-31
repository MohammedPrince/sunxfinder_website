@extends('company.layouts.app')

@section('title', 'Invoices')
@section('breadcrumb', 'Invoices')
@section('eyebrow', 'QUOTES & PROJECTS')
@section('page-title', 'Invoices')
@section('page-description', 'Your company invoices, saved with each offer and available to view or download.')

@push('styles')
    <style>
        .invoice-table-actions { display: flex; align-items: center; gap: 7px; }
        .invoice-table-actions form { margin: 0; }
        .invoice-delete-action { color: #b42318; }
        .invoice-locked-action { color: #7b8794; cursor: not-allowed; }
        .invoice-locked-note { color: #778597; font-size: 10px; white-space: nowrap; }
        .invoice-customer-email { display: block; margin-top: 4px; color: #778597; font-size: 11px; }
        .invoice-empty-state { padding: 30px 15px !important; text-align: center; }
        .invoice-empty-state strong, .invoice-empty-state span { display: block; }
        .invoice-empty-state span { margin-top: 5px; color: #778597; font-size: 12px; }
    </style>
@endpush

@section('content')
    @include('partials.page-guide', [
        'key' => 'company-invoices',
        'title' => 'How projects work',
        'intro' => 'A project is created automatically when a customer accepts your offer.',
        'steps' => [
            '<strong>Start installation</strong> when your team is ready. Until then the project shows as "Not started".',
            '<strong>Update milestones</strong> as each phase is done. Progress is calculated for you.',
            '<strong>Confirm customer handover</strong> as the last step. The project then closes at 100%.',
        ],
        'note' => 'Every update you save (with its message) is shown to the customer in the app.',
    ])

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Your invoices</h2>
                <p>Invoices are listed newest first. Search by invoice number, customer, or quote ID.</p>
            </div>

            <form method="GET" action="{{ route('company.invoices.index') }}" class="table-filters">
                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    maxlength="100" placeholder="Invoice, customer or quote #" aria-label="Search invoices">
                <button type="submit" class="btn btn-light">Search</button>
                @if (!empty($filters['search']))
                    <a href="{{ route('company.invoices.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success" style="margin:16px 20px 0;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" style="margin:16px 20px 0;">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Quote request</th>
                        <th>Invoice date</th>
                        <th>Balance due</th>
                        <th>Offer status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        @php
                            $customerName = data_get($invoice->customer_details, 'name') ?: 'Customer #' . $invoice->customer_id;
                            $isAccepted = $invoice->quotation?->status === 'selected';
                            $canEditInvoice = !$isAccepted
                                && $invoice->quotation?->status === 'submitted'
                                && $invoice->quotation?->quote?->status === 'pending';
                            [$offerStatus, $offerStatusClass] = match ($invoice->quotation?->status) {
                                'selected' => ['Selected', 'pill-green'],
                                'submitted' => ['Awaiting customer', 'pill-yellow'],
                                'not_selected', 'withdrawn' => ['Not selected', 'pill-red'],
                                default => [ucfirst(str_replace('_', ' ', (string) $invoice->quotation?->status)), 'pill-blue'],
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                            <td>
                                <strong>{{ $customerName }}</strong>
                                @if (data_get($invoice->customer_details, 'email'))
                                    <small class="invoice-customer-email">{{ data_get($invoice->customer_details, 'email') }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('company.quote-requests.show', $invoice->quote_id) }}">
                                    #Q-{{ str_pad((string) $invoice->quote_id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
                            <td><strong>{{ number_format((float) $invoice->balance_due, 2) }} {{ $invoice->currency }}</strong></td>
                            <td><span class="pill {{ $offerStatusClass }}">{{ $offerStatus }}</span></td>
                            <td>
                                <div class="invoice-table-actions">
                                    <a class="icon-button" href="{{ route('company.quote-requests.invoice', $invoice->quote_id) }}"
                                        title="View invoice" aria-label="View invoice {{ $invoice->invoice_number }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2.5 12s3.2-6 9.5-6 9.5 6 9.5 6-3.2 6-9.5 6-9.5-6-9.5-6Z" />
                                            <circle cx="12" cy="12" r="2.5" />
                                        </svg>
                                    </a>
                                    <a class="icon-button" href="{{ route('company.quote-requests.invoice.pdf', $invoice->quote_id) }}"
                                        title="Download PDF" aria-label="Download invoice {{ $invoice->invoice_number }} as PDF">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 3v12m0 0 4-4m-4 4-4-4" />
                                            <path d="M5 17v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3" />
                                        </svg>
                                    </a>
                                    @if ($isAccepted)
                                        <span class="icon-button invoice-locked-action" title="Locked after customer acceptance" aria-label="Invoice locked after customer acceptance">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="4" y="10" width="16" height="11" rx="2" />
                                                <path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" />
                                            </svg>
                                        </span>
                                        <small class="invoice-locked-note">Locked after acceptance</small>
                                    @else
                                        @if ($canEditInvoice)
                                            <a class="icon-button" href="{{ route('company.quote-requests.show', ['id' => $invoice->quote_id, 'edit_invoice' => 1]) }}"
                                                title="Edit invoice" aria-label="Edit invoice {{ $invoice->invoice_number }}">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z" />
                                                </svg>
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('company.invoices.destroy', $invoice->id) }}"
                                            onsubmit="return confirm('Delete this invoice? This cannot be undone from this page.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-button invoice-delete-action" type="submit" title="Delete invoice"
                                                aria-label="Delete invoice {{ $invoice->invoice_number }}">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="invoice-empty-state">
                                <strong>No invoices found.</strong>
                                <span>Invoices you create with offers will appear here.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $invoices, 'label' => 'invoices'])
    </section>
@endsection
