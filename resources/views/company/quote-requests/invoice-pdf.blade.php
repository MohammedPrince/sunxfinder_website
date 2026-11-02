{{-- Rendered by mPDF, which has no flex or grid support, so the layout uses tables. --}}
@php
    $company = $invoice->company_details ?? [];
    $customer = $invoice->customer_details ?? [];
    // Once the customer turns this offer down, the invoice shows only their name.
    if ($invoice->quotation?->quote?->hidesCustomerContactFrom((int) $invoice->company_id, $invoice->quotation)) {
        $customer = ['name' => $customer['name'] ?? null];
    }
    $money = fn ($amount) => number_format((float) $amount, 2) . ' ' . $invoice->currency;
    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $isArabic = app()->isLocale('ar');
    $start = $isArabic ? 'right' : 'left';
    $end = $isArabic ? 'left' : 'right';
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">

<head>
    <style>
        body { color: #172b4d; font-family: dejavusans; font-size: 9.5pt; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .label { color: #f4510b; font-size: 9pt; font-weight: bold; letter-spacing: 2px; }
        .company { font-size: 16pt; font-weight: bold; padding: 4pt 0; }
        .muted { color: #64748b; font-size: 8.5pt; line-height: 1.5; }
        .number { font-size: 13pt; font-weight: bold; padding-bottom: 4pt; }
        .right { text-align: {{ $end }}; }
        .rule { border-bottom: 2pt solid #f4510b; padding-bottom: 14pt; }
        .party { padding-top: 16pt; padding-bottom: 16pt; width: 50%; }
        .party-title { color: #64748b; font-size: 7.5pt; font-weight: bold; letter-spacing: 1px; padding-bottom: 5pt; }
        .party-name { font-size: 10.5pt; font-weight: bold; padding-bottom: 3pt; }
        .items th { background-color: #f6f8fb; color: #475569; font-size: 7.5pt; padding: 8pt 7pt; text-align: {{ $start }}; }
        .items td { border-bottom: 0.5pt solid #e8edf3; font-size: 9pt; padding: 8pt 7pt; }
        .items th.num, .items td.num { text-align: {{ $end }}; white-space: nowrap; }
        .notes { padding-top: 16pt; padding-{{ $end }}: 20pt; width: 52%; }
        .notes-body { color: #475569; font-size: 8.5pt; line-height: 1.6; }
        .totals { padding-top: 16pt; width: 48%; }
        .totals td { color: #475569; font-size: 9pt; padding: 3pt 0; }
        .totals td.value { color: #172b4d; font-weight: bold; text-align: {{ $end }}; }
        .totals .balance td { border-top: 1.5pt solid #f4510b; color: #172b4d; font-size: 11pt; font-weight: bold; padding-top: 8pt; }
        .totals .balance td.value { color: #f4510b; font-size: 12pt; }
        .brand { padding-bottom: 12pt; text-align: {{ $start }}; }
        .tagline { color: #10366b; font-size: 10pt; font-weight: bold; padding-top: 3pt; }
        .accent { color: #f15b24; }
    </style>
</head>

<body dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
    <table>
        <tr>
            <td class="brand">
                <img src="{{ public_path('visitors/images/sunxfinder-logo-email.png') }}" width="30mm">
                <div class="tagline">{{ __('company/messages.invoices.brand_find') }} <span class="accent">{{ __('company/messages.invoices.brand_compare') }}</span> {{ __('company/messages.invoices.brand_connect') }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="rule">
                <div class="label">{{ __('company/messages.invoice_pdf.label') }}</div>
                <div class="company">{{ $company['name'] ?? __('company/messages.common.company') }}</div>
                @if (!empty($company['address']))
                    <div class="muted">{{ $company['address'] }}</div>
                @endif
                <div class="muted">
                    {{ collect([$company['email'] ?? null, $company['phone'] ?? null])->filter()->implode(' · ') }}
                </div>
            </td>
            <td class="rule right">
                <div class="number">{{ $invoice->invoice_number }}</div>
                <div class="muted">{{ __('company/messages.invoices.date_line', ['date' => \Illuminate\Support\Carbon::parse($invoice->invoice_date)->translatedFormat('d M Y')]) }}</div>
                <div class="muted">{{ __('company/messages.invoices.quote_line', ['id' => str_pad((string) $invoice->quote_id, 5, '0', STR_PAD_LEFT)]) }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="party">
                <div class="party-title">{{ __('company/messages.invoice_pdf.bill_to') }}</div>
                <div class="party-name">{{ $customer['name'] ?? __('company/messages.common.customer') }}</div>
                @foreach (['email', 'phone', 'address'] as $field)
                    @if (!empty($customer[$field]))
                        <div class="muted">{{ $customer[$field] }}</div>
                    @endif
                @endforeach
            </td>
            <td class="party">
                <div class="party-title">{{ __('company/messages.invoice_pdf.ship_to') }}</div>
                <div class="party-name">{{ $customer['name'] ?? __('company/messages.common.customer') }}</div>
                <div class="muted">{{ $customer['address'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('company/messages.invoice_pdf.description') }}</th>
                <th class="num">{{ __('company/messages.invoice_pdf.qty') }}</th>
                <th class="num">{{ __('company/messages.invoice_pdf.unit_price') }}</th>
                <th class="num">{{ __('company/messages.invoice_pdf.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items ?? [] as $item)
                <tr>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td class="num">{{ $quantity($item['quantity'] ?? 0) }}</td>
                    <td class="num">{{ $money($item['unit_price'] ?? 0) }}</td>
                    <td class="num">{{ $money($item['total'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tr>
            <td class="notes">
                @if ($invoice->payment_instructions)
                    <div class="party-title">{{ __('company/messages.invoice_pdf.remarks') }}</div>
                    <div class="notes-body">{!! nl2br(e($invoice->payment_instructions)) !!}</div>
                @endif
            </td>
            <td class="totals">
                <table>
                    <tr><td>{{ __('company/messages.invoices.subtotal') }}</td><td class="value">{{ $money($invoice->subtotal) }}</td></tr>
                    <tr><td>{{ __('company/messages.invoices.discount') }}</td><td class="value">− {{ $money($invoice->discount) }}</td></tr>
                    <tr><td>{{ __('company/messages.invoices.subtotal_less') }}</td><td class="value">{{ $money($invoice->subtotal_less_discount) }}</td></tr>
                    <tr><td>{{ __('company/messages.invoices.tax_line', ['rate' => number_format((float) $invoice->tax_rate, 2)]) }}</td><td class="value">{{ $money($invoice->tax) }}</td></tr>
                    <tr><td>{{ __('company/messages.invoices.shipping') }}</td><td class="value">{{ $money($invoice->shipping) }}</td></tr>
                    <tr class="balance"><td>{{ __('company/messages.invoices.balance_due') }}</td><td class="value">{{ $money($invoice->balance_due) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
