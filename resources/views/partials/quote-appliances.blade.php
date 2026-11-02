{{-- Devices the customer picked in the app calculator. Usage: @include('partials.quote-appliances', ['quote' => $quote]) --}}
@php
    $applianceItems = $quote->applianceItems();
    $totalWatts = $applianceItems->sum('total_watts');
    $totalDevices = $applianceItems->sum('qty');
    $appliancesId = 'quote-appliances-' . $quote->id;
@endphp

<div class="quote-subsection quote-appliances" id="{{ $appliancesId }}">
    <div class="quote-section-heading" style="padding:0 0 12px">
        <div>
            <h3 style="margin:0">{{ company_text('shared.appliances.customer_devices', 'Customer devices') }}</h3>
            <p class="gray-text" style="margin:4px 0 0">
                {{ company_text('shared.appliances.devices_intro', 'The exact appliances the customer chose in the app calculator, with their watts.') }}
            </p>
        </div>
        @if ($applianceItems->isNotEmpty())
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <span class="pill pill-blue">{{ company_portal() ? trans_choice('company/messages.shared.appliances.device_count', $totalDevices) : $totalDevices . ' ' . \Illuminate\Support\Str::plural('device', $totalDevices) }} · {{ number_format($totalWatts) }} W</span>
                <button type="button" class="btn btn-light no-print" data-appliances-toggle aria-expanded="false" aria-controls="{{ $appliancesId }}-body"
                    data-show="{{ company_text('shared.appliances.show', 'Show devices') }}"
                    data-hide="{{ company_text('shared.appliances.hide', 'Hide devices') }}">{{ company_text('shared.appliances.show', 'Show devices') }}</button>
            </div>
        @endif
    </div>

    @if ($applianceItems->isNotEmpty())
        <div class="quote-appliances-body" id="{{ $appliancesId }}-body" hidden>
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
                            <tr data-appliance-row>
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
                                <small class="cell-note" dir="ltr">{{ number_format($totalWatts / 1000, 2) }} kW</small>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($applianceItems->count() > 10)
                <div class="quote-appliances-pager no-print" data-appliances-pager
                    data-page-of="{{ company_text('shared.appliances.page_of', 'Page :page of :pages') }}">
                    <button type="button" class="btn btn-light" data-page-step="-1">{{ company_text('shared.appliances.previous', 'Previous') }}</button>
                    <span data-page-label></span>
                    <button type="button" class="btn btn-light" data-page-step="1">{{ company_text('shared.appliances.next', 'Next') }}</button>
                </div>
            @endif
        </div>

        <style>
            .quote-appliances-pager { display: flex; gap: 10px; align-items: center; justify-content: center; margin-top: 12px; }
            .quote-appliances-pager span { color: #64748b; font-size: 13px; }
            @media print {
                .quote-appliances-body[hidden] { display: block !important; }
                .quote-appliances [data-appliance-row] { display: table-row !important; }
            }
        </style>

        <script>
            (() => {
                const box = document.getElementById(@json($appliancesId));
                const body = box.querySelector('.quote-appliances-body');
                const toggle = box.querySelector('[data-appliances-toggle]');
                const rows = [...box.querySelectorAll('[data-appliance-row]')];
                const pager = box.querySelector('[data-appliances-pager]');
                const perPage = 10;
                const pages = Math.ceil(rows.length / perPage);
                let page = 1;

                const show = () => {
                    rows.forEach((row, i) => row.hidden = pager && Math.floor(i / perPage) + 1 !== page);
                    if (!pager) return;
                    pager.querySelector('[data-page-label]').textContent = pager.dataset.pageOf.replace(':pages', pages).replace(':page', page);
                    pager.querySelector('[data-page-step="-1"]').disabled = page === 1;
                    pager.querySelector('[data-page-step="1"]').disabled = page === pages;
                };

                toggle.addEventListener('click', () => {
                    body.hidden = !body.hidden;
                    toggle.setAttribute('aria-expanded', String(!body.hidden));
                    toggle.textContent = body.hidden ? toggle.dataset.show : toggle.dataset.hide;
                });
                pager?.addEventListener('click', event => {
                    const step = event.target.closest('[data-page-step]');
                    if (!step) return;
                    page = Math.min(pages, Math.max(1, page + Number(step.dataset.pageStep)));
                    show();
                });
                show();
            })();
        </script>
    @else
        <p class="gray-text" style="margin:0">
            {{ company_text('shared.appliances.no_devices', 'No device list was sent with this request. Older app versions only send the load totals above.') }}
        </p>
    @endif
</div>
