@extends('company.layouts.app')

@section('title', __('company/messages.invoices.title'))
@section('breadcrumb', __('company/messages.invoices.title'))
@section('eyebrow', __('company/messages.invoices.eyebrow'))
@section('page-title', __('company/messages.invoices.title'))
@section('page-description', __('company/messages.invoices.page_description'))

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
        'title' => __('company/messages.invoices.guide_title'),
        'intro' => __('company/messages.invoices.guide_intro'),
        'steps' => [
            __('company/messages.invoices.guide_1'),
            __('company/messages.invoices.guide_2'),
            __('company/messages.invoices.guide_3'),
        ],
        'note' => __('company/messages.invoices.guide_note'),
    ])

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.invoices.your_invoices') }}</h2>
                <p>{{ __('company/messages.invoices.list_sub') }}</p>
            </div>

            <form method="GET" action="{{ route('company.invoices.index') }}" class="table-filters">
                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    maxlength="100" placeholder="{{ __('company/messages.invoices.search_placeholder') }}" aria-label="{{ __('company/messages.invoices.search_label') }}">
                <button type="submit" class="btn btn-light">{{ __('company/messages.common.search') }}</button>
                @if (!empty($filters['search']))
                    <a href="{{ route('company.invoices.index') }}" class="btn btn-light">{{ __('company/messages.common.clear') }}</a>
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
                        <th>{{ __('company/messages.invoices.invoice') }}</th>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.invoices.quote_request') }}</th>
                        <th>{{ __('company/messages.invoices.invoice_date') }}</th>
                        <th>{{ __('company/messages.invoices.balance_due') }}</th>
                        <th>{{ __('company/messages.invoices.offer_status') }}</th>
                        <th>{{ __('company/messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        @php
                            $customerName = data_get($invoice->customer_details, 'name') ?: __('company/messages.common.customer_number', ['id' => $invoice->customer_id]);
                            $isAccepted = $invoice->quotation?->status === 'selected';
                            $canEditInvoice = !$isAccepted
                                && $invoice->quotation?->status === 'submitted'
                                && $invoice->quotation?->quote?->status === 'pending';
                            [$offerStatus, $offerStatusClass] = match ($invoice->quotation?->status) {
                                'selected' => [__('company/messages.common.selected'), 'pill-green'],
                                'submitted' => [__('company/messages.common.awaiting_customer'), 'pill-yellow'],
                                'not_selected', 'withdrawn' => [__('company/messages.common.not_selected'), 'pill-red'],
                                default => [ucfirst(str_replace('_', ' ', (string) $invoice->quotation?->status)), 'pill-blue'],
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                            <td>
                                <strong>{{ $customerName }}</strong>
                                @if (data_get($invoice->customer_details, 'email') && !$invoice->quotation?->quote?->hidesCustomerContactFrom((int) $invoice->company_id, $invoice->quotation))
                                    <small class="invoice-customer-email">{{ data_get($invoice->customer_details, 'email') }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('company.quote-requests.show', $invoice->quote_id) }}">
                                    #Q-{{ str_pad((string) $invoice->quote_id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->translatedFormat('d M Y') }}</td>
                            <td><strong>{{ number_format((float) $invoice->balance_due, 2) }} {{ $invoice->currency }}</strong></td>
                            <td><span class="pill {{ $offerStatusClass }}">{{ $offerStatus }}</span></td>
                            <td>
                                <div class="invoice-table-actions">
                                    <a class="icon-button" href="{{ route('company.quote-requests.invoice', $invoice->quote_id) }}"
                                        title="{{ __('company/messages.invoices.view') }}" aria-label="{{ __('company/messages.invoices.view_label', ['number' => $invoice->invoice_number]) }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2.5 12s3.2-6 9.5-6 9.5 6 9.5 6-3.2 6-9.5 6-9.5-6-9.5-6Z" />
                                            <circle cx="12" cy="12" r="2.5" />
                                        </svg>
                                    </a>
                                    <a class="icon-button" href="{{ route('company.quote-requests.invoice.pdf', $invoice->quote_id) }}"
                                        title="{{ __('company/messages.invoices.download') }}" aria-label="{{ __('company/messages.invoices.download_label', ['number' => $invoice->invoice_number]) }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 3v12m0 0 4-4m-4 4-4-4" />
                                            <path d="M5 17v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3" />
                                        </svg>
                                    </a>
                                    @if ($isAccepted)
                                        <span class="icon-button invoice-locked-action" title="{{ __('company/messages.invoices.locked_title') }}" aria-label="{{ __('company/messages.invoices.locked_label') }}">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="4" y="10" width="16" height="11" rx="2" />
                                                <path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" />
                                            </svg>
                                        </span>
                                        <small class="invoice-locked-note">{{ __('company/messages.invoices.locked_note') }}</small>
                                    @else
                                        @if ($canEditInvoice)
                                            <a class="icon-button" href="{{ route('company.quote-requests.show', ['id' => $invoice->quote_id, 'edit_invoice' => 1]) }}"
                                                title="{{ __('company/messages.invoices.edit') }}" aria-label="{{ __('company/messages.invoices.edit_label', ['number' => $invoice->invoice_number]) }}">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z" />
                                                </svg>
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('company.invoices.destroy', $invoice->id) }}"
                                            onsubmit="return confirm(@js(__('company/messages.invoices.delete_confirm')));">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-button invoice-delete-action" type="submit" title="{{ __('company/messages.invoices.delete') }}"
                                                aria-label="{{ __('company/messages.invoices.delete_label', ['number' => $invoice->invoice_number]) }}">
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
                                <strong>{{ __('company/messages.invoices.empty_title') }}</strong>
                                <span>{{ __('company/messages.invoices.empty_body') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $invoices, 'label' => 'invoices'])
    </section>
@endsection
