{{-- Rendered by mPDF, which has no flex or grid support, so the layout uses tables. --}}
@php
    $company = $invoice->company_details ?? [];
    $customer = $invoice->customer_details ?? [];
    $money = fn ($amount) => number_format((float) $amount, 2) . ' ' . $invoice->currency;
    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp
<html>

<head>
    <style>
        body { color: #172b4d; font-family: dejavusans; font-size: 9.5pt; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .label { color: #f4510b; font-size: 9pt; font-weight: bold; letter-spacing: 2px; }
        .company { font-size: 16pt; font-weight: bold; padding: 4pt 0; }
        .muted { color: #64748b; font-size: 8.5pt; line-height: 1.5; }
        .number { font-size: 13pt; font-weight: bold; padding-bottom: 4pt; }
        .right { text-align: right; }
        .rule { border-bottom: 2pt solid #f4510b; padding-bottom: 14pt; }
        .party { padding-top: 16pt; padding-bottom: 16pt; width: 50%; }
        .party-title { color: #64748b; font-size: 7.5pt; font-weight: bold; letter-spacing: 1px; padding-bottom: 5pt; }
        .party-name { font-size: 10.5pt; font-weight: bold; padding-bottom: 3pt; }
        .items th { background-color: #f6f8fb; color: #475569; font-size: 7.5pt; padding: 8pt 7pt; text-align: left; }
        .items td { border-bottom: 0.5pt solid #e8edf3; font-size: 9pt; padding: 8pt 7pt; }
        .items th.num, .items td.num { text-align: right; white-space: nowrap; }
        .notes { padding-top: 16pt; padding-right: 20pt; width: 52%; }
        .notes-body { color: #475569; font-size: 8.5pt; line-height: 1.6; }
        .totals { padding-top: 16pt; width: 48%; }
        .totals td { color: #475569; font-size: 9pt; padding: 3pt 0; }
        .totals td.value { color: #172b4d; font-weight: bold; text-align: right; }
        .totals .balance td { border-top: 1.5pt solid #f4510b; color: #172b4d; font-size: 11pt; font-weight: bold; padding-top: 8pt; }
        .totals .balance td.value { color: #f4510b; font-size: 12pt; }
    </style>
</head>

<body>
    <table>
        <tr>
            <td class="rule">
                <div class="label">INVOICE</div>
                <div class="company">{{ $company['name'] ?? 'Company' }}</div>
                @if (!empty($company['address']))
                    <div class="muted">{{ $company['address'] }}</div>
                @endif
                <div class="muted">
                    {{ collect([$company['email'] ?? null, $company['phone'] ?? null])->filter()->implode(' · ') }}
                </div>
            </td>
            <td class="rule right">
                <div class="number">{{ $invoice->invoice_number }}</div>
                <div class="muted">Date: {{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</div>
                <div class="muted">Quote: #Q-{{ str_pad((string) $invoice->quote_id, 5, '0', STR_PAD_LEFT) }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="party">
                <div class="party-title">BILL TO</div>
                <div class="party-name">{{ $customer['name'] ?? 'Customer' }}</div>
                @foreach (['email', 'phone', 'address'] as $field)
                    @if (!empty($customer[$field]))
                        <div class="muted">{{ $customer[$field] }}</div>
                    @endif
                @endforeach
            </td>
            <td class="party">
                <div class="party-title">SHIP TO</div>
                <div class="party-name">{{ $customer['name'] ?? 'Customer' }}</div>
                <div class="muted">{{ $customer['address'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>DESCRIPTION</th>
                <th class="num">QTY</th>
                <th class="num">UNIT PRICE</th>
                <th class="num">TOTAL</th>
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
                    <div class="party-title">REMARKS / PAYMENT INSTRUCTIONS</div>
                    <div class="notes-body">{!! nl2br(e($invoice->payment_instructions)) !!}</div>
                @endif
            </td>
            <td class="totals">
                <table>
                    <tr><td>Subtotal</td><td class="value">{{ $money($invoice->subtotal) }}</td></tr>
                    <tr><td>Discount</td><td class="value">− {{ $money($invoice->discount) }}</td></tr>
                    <tr><td>Subtotal less discount</td><td class="value">{{ $money($invoice->subtotal_less_discount) }}</td></tr>
                    <tr><td>Tax ({{ number_format((float) $invoice->tax_rate, 2) }}%)</td><td class="value">{{ $money($invoice->tax) }}</td></tr>
                    <tr><td>Shipping / handling</td><td class="value">{{ $money($invoice->shipping) }}</td></tr>
                    <tr class="balance"><td>Balance due</td><td class="value">{{ $money($invoice->balance_due) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
