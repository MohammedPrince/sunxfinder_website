@extends('company.layouts.app')

@section('title', __('company/messages.invoices.page_title_offer'))
@section('breadcrumb', __('company/messages.navigation.quote_requests'))
@section('eyebrow', __('company/messages.quote_requests.eyebrow'))
@section('page-title', __('company/messages.invoices.page_title_lower'))
@section('page-description', __('company/messages.invoices.invoice_description'))

@section('page-actions')
    <div class="quote-page-actions no-print">
        <a href="{{ route('company.invoices.index') }}" class="btn btn-light">{{ __('company/messages.invoices.all_invoices') }}</a>
        <a href="{{ route('company.quote-requests.show', $invoice->quote_id) }}" class="btn btn-light">{{ __('company/messages.invoices.back_request') }}</a>
        <button type="button" class="btn btn-light" onclick="window.print()">{{ __('company/messages.invoices.print') }}</button>
        <a href="{{ route('company.quote-requests.invoice.pdf', $invoice->quote_id) }}" class="btn btn-primary">{{ __('company/messages.invoices.download') }}</a>
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
                <span class="company-invoice-label">{{ __('company/messages.invoices.label') }}</span>
                <h2>{{ $company['name'] ?? __('company/messages.common.company') }}</h2>
                <p>{{ $company['address'] ?? '' }}</p>
                <p>{{ $company['email'] ?? '' }}@if (!empty($company['phone'])) · {{ $company['phone'] }}@endif</p>
            </div>
            <div class="company-invoice-number">
                <strong>{{ $invoice->invoice_number }}</strong>
                <span>{{ __('company/messages.invoices.date_line', ['date' => \Illuminate\Support\Carbon::parse($invoice->invoice_date)->translatedFormat('d M Y')]) }}</span>
                <span>{{ __('company/messages.invoices.quote_line', ['id' => str_pad((string) $invoice->quote_id, 5, '0', STR_PAD_LEFT)]) }}</span>
            </div>
        </header>

        <section class="company-invoice-parties">
            <div><span>{{ __('company/messages.invoices.bill_to') }}</span><strong>{{ $customer['name'] ?? __('company/messages.common.customer') }}</strong><p>{{ $customer['email'] ?? '' }}</p><p>{{ $customer['phone'] ?? '' }}</p><p>{{ $customer['address'] ?? '' }}</p></div>
            <div><span>{{ __('company/messages.invoices.ship_to') }}</span><strong>{{ $customer['name'] ?? __('company/messages.common.customer') }}</strong><p>{{ $customer['address'] ?? '' }}</p></div>
        </section>

        <div class="company-invoice-table-wrap">
            <table class="company-invoice-table">
                <thead><tr><th>{{ __('company/messages.invoices.description') }}</th><th>{{ __('company/messages.invoices.qty') }}</th><th>{{ __('company/messages.invoices.unit_price') }}</th><th>{{ __('company/messages.invoices.total') }}</th></tr></thead>
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
                    <span>{{ __('company/messages.invoices.remarks') }}</span>
                    <p>{{ $invoice->payment_instructions }}</p>
                @endif
            </div>
            <dl class="company-invoice-totals">
                <div><dt>{{ __('company/messages.invoices.subtotal') }}</dt><dd>{{ $formatMoney($invoice->subtotal) }} {{ $invoice->currency }}</dd></div>
                <div><dt>{{ __('company/messages.invoices.discount') }}</dt><dd>− {{ $formatMoney($invoice->discount) }} {{ $invoice->currency }}</dd></div>
                <div><dt>{{ __('company/messages.invoices.subtotal_less') }}</dt><dd>{{ $formatMoney($invoice->subtotal_less_discount) }} {{ $invoice->currency }}</dd></div>
                <div><dt>{{ __('company/messages.invoices.tax_line', ['rate' => $formatMoney($invoice->tax_rate)]) }}</dt><dd>{{ $formatMoney($invoice->tax) }} {{ $invoice->currency }}</dd></div>
                <div><dt>{{ __('company/messages.invoices.shipping') }}</dt><dd>{{ $formatMoney($invoice->shipping) }} {{ $invoice->currency }}</dd></div>
                <div class="is-balance"><dt>{{ __('company/messages.invoices.balance_due') }}</dt><dd>{{ $formatMoney($invoice->balance_due) }} {{ $invoice->currency }}</dd></div>
            </dl>
        </section>
    </article>
@endsection
