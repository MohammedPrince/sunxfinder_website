{{-- Devices the customer picked in the app calculator. Usage: @include('partials.quote-appliances', ['quote' => $quote]) --}}
@php
    $applianceItems = $quote->applianceItems();
    $totalWatts = $applianceItems->sum('total_watts');
    $totalDevices = $applianceItems->sum('qty');
@endphp

<div class="quote-subsection">
    <div class="quote-section-heading" style="padding:0 0 12px">
        <div>
            <h3 style="margin:0">{{ company_text('shared.appliances.customer_devices', 'Customer devices') }}</h3>
            <p class="gray-text" style="margin:4px 0 0">
                {{ company_text('shared.appliances.devices_intro', 'The exact appliances the customer chose in the app calculator, with their watts.') }}
            </p>
        </div>
        @if ($applianceItems->isNotEmpty())
            <span class="pill pill-blue">{{ company_portal() ? trans_choice('company/messages.shared.appliances.device_count', $totalDevices) : $totalDevices . ' ' . \Illuminate\Support\Str::plural('device', $totalDevices) }} · {{ number_format($totalWatts) }} W</span>
        @endif
    </div>

    @if ($applianceItems->isNotEmpty())
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ company_text('shared.appliances.device', 'Device') }}</th>
                        <th>{{ company_text('shared.appliances.group', 'Group') }}</th>
                        <th style="text-align:right">{{ company_text('shared.appliances.watts', 'Watts') }}</th>
                        <th style="text-align:right">{{ company_text('shared.appliances.qty', 'Qty') }}</th>
                        <th style="text-align:right">{{ company_text('shared.appliances.total_watts', 'Total watts') }}</th>
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
                        <td colspan="3"><strong>{{ company_text('shared.appliances.total_load', 'Total connected load') }}</strong>
                            <small class="cell-note">{{ company_text('shared.appliances.same_time', 'If every device runs at the same time.') }}</small>
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
            {{ company_text('shared.appliances.no_devices', 'No device list was sent with this request. Older app versions only send the load totals above.') }}
        </p>
    @endif
</div>
