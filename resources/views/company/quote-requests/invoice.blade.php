@extends('company.layouts.app')

@section('title', 'Offer Invoice')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'COMPANY ENQUIRIES')
@section('page-title', 'Offer invoice')
@section('page-description', 'Review, print, or download the invoice as a PDF.')

@section('page-actions')
    <div class="quote-page-actions no-print">
        <a href="{{ route('company.quote-requests.show', $invoice->quote_id) }}" class="btn btn-light">&larr; Back to request</a>
        <button type="button" class="btn btn-light" onclick="window.print()">Print</button>
        <a href="{{ route('company.quote-requests.invoice.pdf', $invoice->quote_id) }}" class="btn btn-primary">Download PDF</a>
    </div>
@endsection

@section('content')
    @php
        $company = $invoice->company_details ?? [];
        $customer = $invoice->customer_details ?? [];
        $formatMoney = fn ($amount) => number_format((float) $amount, 2);
    @endphp

    <article class="company-invoice">
        <header class="company-invoice-header">
            <div>
                <span class="company-invoice-label">INVOICE</span>
                <h2>{{ $company['name'] ?? 'Company' }}</h2>
                <p>{{ $company['address'] ?? '' }}</p>
                <p>{{ $company['email'] ?? '' }}@if (!empty($company['phone'])) · {{ $company['phone'] }}@endif</p>
            </div>
            <div class="company-invoice-number">
                <strong>{{ $invoice->invoice_number }}</strong>
                <span>Date: {{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span>
                <span>Quote: #Q-{{ str_pad((string) $invoice->quote_id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </header>

        <section class="company-invoice-parties">
            <div><span>Bill to</span><strong>{{ $customer['name'] ?? 'Customer' }}</strong><p>{{ $customer['email'] ?? '' }}</p><p>{{ $customer['phone'] ?? '' }}</p><p>{{ $customer['address'] ?? '' }}</p></div>
            <div><span>Ship to</span><strong>{{ $customer['name'] ?? 'Customer' }}</strong><p>{{ $customer['address'] ?? '' }}</p></div>
        </section>

        <div class="company-invoice-table-wrap">
            <table class="company-invoice-table">
                <thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($invoice->items ?? [] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? '' }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 2), '0'), '.') }}</td>
                            <td>{{ $formatMoney($item['unit_price'] ?? 0) }} {{ $invoice->currency }}</td>
                            <td>{{ $formatMoney($item['total'] ?? 0) }} {{ $invoice->currency }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <section class="company-invoice-footer">
            <div class="company-invoice-notes">
                @if ($invoice->payment_instructions)
                    <span>Remarks / payment instructions</span>
                    <p>{{ $invoice->payment_instructions }}</p>
                @endif
            </div>
            <dl class="company-invoice-totals">
                <div><dt>Subtotal</dt><dd>{{ $formatMoney($invoice->subtotal) }} {{ $invoice->currency }}</dd></div>
                <div><dt>Discount</dt><dd>− {{ $formatMoney($invoice->discount) }} {{ $invoice->currency }}</dd></div>
                <div><dt>Subtotal less discount</dt><dd>{{ $formatMoney($invoice->subtotal_less_discount) }} {{ $invoice->currency }}</dd></div>
                <div><dt>Tax ({{ $formatMoney($invoice->tax_rate) }}%)</dt><dd>{{ $formatMoney($invoice->tax) }} {{ $invoice->currency }}</dd></div>
                <div><dt>Shipping / handling</dt><dd>{{ $formatMoney($invoice->shipping) }} {{ $invoice->currency }}</dd></div>
                <div class="is-balance"><dt>Balance due</dt><dd>{{ $formatMoney($invoice->balance_due) }} {{ $invoice->currency }}</dd></div>
            </dl>
        </section>
    </article>
@endsection
