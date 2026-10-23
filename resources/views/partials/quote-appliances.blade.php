{{-- Devices the customer picked in the app calculator. Usage: @include('partials.quote-appliances', ['quote' => $quote]) --}}
@php
    $applianceItems = $quote->applianceItems();
    $totalWatts = $applianceItems->sum('total_watts');
    $totalDevices = $applianceItems->sum('qty');
@endphp

<div class="quote-subsection">
    <div class="quote-section-heading" style="padding:0 0 12px">
        <div>
            <h3 style="margin:0">Customer devices</h3>
            <p class="gray-text" style="margin:4px 0 0">
                The exact appliances the customer chose in the app calculator, with their watts.
            </p>
        </div>
        @if ($applianceItems->isNotEmpty())
            <span class="pill pill-blue">{{ $totalDevices }} {{ \Illuminate\Support\Str::plural('device', $totalDevices) }} · {{ number_format($totalWatts) }} W</span>
        @endif
    </div>

    @if ($applianceItems->isNotEmpty())
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Group</th>
                        <th style="text-align:right">Watts</th>
                        <th style="text-align:right">Qty</th>
                        <th style="text-align:right">Total watts</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applianceItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['name'] }}</strong>
                                @if ($item['ar_name'])
                                    <small class="cell-note" dir="rtl" lang="ar">{{ $item['ar_name'] }}</small>
                                @endif
                            </td>
                            <td>{{ $item['group'] ?: '—' }}</td>
                            <td style="text-align:right">{{ number_format($item['watts']) }} W</td>
                            <td style="text-align:right">× {{ $item['qty'] }}</td>
                            <td style="text-align:right"><strong>{{ number_format($item['total_watts']) }} W</strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total connected load</strong>
                            <small class="cell-note">If every device runs at the same time.</small>
                        </td>
                        <td style="text-align:right"><strong>{{ $totalDevices }}</strong></td>
                        <td style="text-align:right"><strong>{{ number_format($totalWatts) }} W</strong>
                            <small class="cell-note">{{ number_format($totalWatts / 1000, 2) }} kW</small>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="gray-text" style="margin:0">
            No device list was sent with this request. Older app versions only send the load totals above.
        </p>
    @endif
</div>
