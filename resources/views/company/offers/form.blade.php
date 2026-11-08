@extends('company.layouts.app')

@php
    // Editing, a copy of an earlier offer (?from=), or a new one.
    $base = $offer ?? $source;
    $pageTitle = $offer ? __('company/messages.package_offers.edit') : __('company/messages.package_offers.create');
    $items = array_values(old('items', $base?->items ?? [
        ['type' => 'solar_panel', 'description' => __('company/messages.quote.sug_panels'), 'quantity' => 6, 'unit_price' => ''],
        ['type' => 'inverter', 'description' => __('company/messages.quote.sug_inverter'), 'quantity' => 1, 'unit_price' => ''],
        ['type' => 'battery', 'description' => __('company/messages.quote.sug_batteries'), 'quantity' => 2, 'unit_price' => ''],
        ['type' => 'other', 'description' => __('company/messages.quote.sug_installation'), 'quantity' => 1, 'unit_price' => ''],
    ]));
    $currency = old('currency', $base?->currency ?? 'SDG');
    $expiresAt = old('expires_at', $offer?->expires_at?->format('Y-m-d\TH:i'));
@endphp

@section('title', $pageTitle)
@section('breadcrumb', __('company/messages.package_offers.title'))
@section('eyebrow', __('company/messages.package_offers.eyebrow'))
@section('page-title', $pageTitle)
@section('page-description', __('company/messages.package_offers.form_description'))

@section('page-actions')
    <a href="{{ $offer ? route('company.offers.show', $offer->id) : route('company.offers.index') }}" class="btn btn-light">
        {{ __('company/messages.package_offers.back') }}
    </a>
@endsection

@section('content')
    @include('company.offers.partials.alerts')

    @if ($source)
        <div class="alert alert-info">{{ __('company/messages.package_offers.copy_note', ['title' => $source->title_en]) }}</div>
    @endif

    <form method="POST" id="packageOfferForm"
        action="{{ $offer ? route('company.offers.update', $offer->id) : route('company.offers.store') }}">
        @csrf
        @if ($offer)
            @method('PUT')
        @endif

        <div class="package-offer-layout">
            <div class="package-offer-main">
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>{{ __('company/messages.package_offers.section_details') }}</h2>
                            <p>{{ __('company/messages.package_offers.section_details_sub') }}</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title_en">{{ __('company/messages.package_offers.title_en') }} <b style="color:red">*</b></label>
                                <input class="form-control" id="title_en" name="title_en" type="text" maxlength="150" required
                                    value="{{ old('title_en', $base?->title_en) }}" placeholder="{{ __('company/messages.package_offers.title_placeholder') }}">
                                @error('title_en')<small class="field-error">{{ $message }}</small>@enderror
                            </div>
                            <div class="form-group">
                                <label for="title_ar">{{ __('company/messages.package_offers.title_ar') }}</label>
                                <input class="form-control" id="title_ar" name="title_ar" type="text" maxlength="150" dir="rtl" lang="ar"
                                    value="{{ old('title_ar', $base?->title_ar) }}" placeholder="مثال: منظومة 5 كيلوواط مع التركيب">
                            </div>
                            <div class="form-group">
                                <label for="description_en">{{ __('company/messages.package_offers.description_en') }}</label>
                                <textarea class="form-control" id="description_en" name="description_en" rows="4" maxlength="2000"
                                    placeholder="{{ __('company/messages.package_offers.description_placeholder') }}">{{ old('description_en', $base?->description_en) }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="description_ar">{{ __('company/messages.package_offers.description_ar') }}</label>
                                <textarea class="form-control" id="description_ar" name="description_ar" rows="4" maxlength="2000" dir="rtl" lang="ar"
                                    placeholder="ماذا يشمل العرض: الضمان، التركيب، مدة التنفيذ...">{{ old('description_ar', $base?->description_ar) }}</textarea>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>{{ __('company/messages.package_offers.section_limits') }}</h2>
                            <p>{{ __('company/messages.package_offers.section_limits_sub') }}</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="max_load_kw">{{ __('company/messages.package_offers.max_load') }} (kW) <b style="color:red">*</b></label>
                                <input class="form-control" id="max_load_kw" name="max_load_kw" type="number" min="0.1" max="1000" step="0.1" required
                                    value="{{ old('max_load_kw', $base ? $base->maxLoadLabel() : '') }}" placeholder="5">
                                <small class="form-hint">{{ __('company/messages.package_offers.max_load_hint') }}</small>
                                @error('max_load_kw')<small class="field-error">{{ $message }}</small>@enderror
                            </div>
                            <div class="form-group">
                                <label for="currency">{{ __('company/messages.quote.currency') }} <b style="color:red">*</b></label>
                                <select class="select-control" id="currency" name="currency" required>
                                    @foreach (['SDG', 'USD'] as $code)
                                        <option value="{{ $code }}" @selected($currency === $code)>{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="expires_at">{{ __('company/messages.package_offers.valid_until') }}</label>
                                <input class="form-control" id="expires_at" name="expires_at" type="datetime-local" value="{{ $expiresAt }}">
                                <small class="form-hint">{{ __('company/messages.package_offers.valid_until_hint') }}</small>
                                @error('expires_at')<small class="field-error">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-body offer-invoice-content">
                        <div class="offer-invoice-items-head">
                            <div>
                                <strong>{{ __('company/messages.package_offers.items') }}</strong>
                                <span>{{ __('company/messages.package_offers.items_sub') }}</span>
                            </div>
                            <button type="button" class="btn btn-light" id="addInvoiceItem">{{ __('company/messages.quote.add_item') }}</button>
                        </div>
                        @if ($products->isEmpty())
                            <small class="form-hint">
                                {{ __('company/messages.package_offers.no_products') }}
                                <a href="{{ route('company.products.create') }}">{{ __('company/messages.package_offers.add_products') }}</a>
                            </small>
                        @endif

                        <div class="offer-invoice-items" id="invoiceItems">
                            @foreach ($items as $item)
                                @include('company.quote-requests.partials.offer-invoice-item', ['name' => 'items', 'index' => $loop->index, 'item' => $item, 'products' => $products])
                            @endforeach
                        </div>
                        <div class="offer-invoice-suggestions">
                            <span>{{ __('company/messages.quote.quick_add') }}</span>
                            @foreach ([
                                ['solar_panel', __('company/messages.quote.sug_panels')],
                                ['inverter', __('company/messages.quote.sug_inverter')],
                                ['battery', __('company/messages.quote.sug_batteries')],
                                ['other', __('company/messages.quote.sug_mounting')],
                                ['other', __('company/messages.quote.sug_cables')],
                                ['other', __('company/messages.quote.sug_installation')],
                            ] as [$type, $suggestion])
                                <button type="button" data-invoice-suggestion="{{ $suggestion }}" data-type="{{ $type }}">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                        <template id="invoiceItemTemplate">
                            @include('company.quote-requests.partials.offer-invoice-item', ['name' => 'items', 'index' => '__INDEX__', 'item' => [], 'products' => $products])
                        </template>
                        @error('items')<small class="field-error">{{ $message }}</small>@enderror

                        <div class="offer-invoice-adjustments">
                            <div class="offer-form-group">
                                <label for="discount">{{ __('company/messages.quote.discount') }}</label>
                                <input class="form-control" id="discount" name="discount" type="number" min="0" step="0.01"
                                    value="{{ old('discount', $base?->discount ?? 0) }}">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="package-offer-aside">
                <div class="offer-summary-card">
                    <h3>{{ __('company/messages.quote.offer_summary') }}</h3>
                    <dl>
                        <div><dt>{{ __('company/messages.package_offers.max_load') }}</dt><dd id="summaryLoad">—</dd></div>
                        <div><dt>{{ __('company/messages.quote.subtotal') }}</dt><dd id="summarySubtotal">0.00</dd></div>
                        <div><dt>{{ __('company/messages.quote.discount') }}</dt><dd id="summaryDiscount">0.00</dd></div>
                    </dl>
                    <div class="offer-summary-total">
                        <span>{{ __('company/messages.package_offers.customer_pays') }}</span>
                        <strong><span id="summaryTotal">0.00</span> <small data-offer-currency>{{ $currency }}</small></strong>
                    </div>
                    <small class="offer-summary-minimum" id="offerMinimumWarning" hidden>{{ __('company/messages.quote.minimum_warning') }}</small>
                </div>

                <div class="offer-notice">
                    <p>{{ __('company/messages.package_offers.save_notice') }}</p>
                </div>

                <button type="submit" class="btn btn-primary package-offer-submit" id="submitOfferButton">
                    {{ __('company/messages.package_offers.save') }}
                </button>
            </aside>
        </div>
    </form>

    @include('company.quote-requests.partials.offer-item-picker-script')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('packageOfferForm');
            const items = document.getElementById('invoiceItems');
            const template = document.getElementById('invoiceItemTemplate');
            const submit = document.getElementById('submitOfferButton');
            let nextIndex = items.querySelectorAll('[data-invoice-item]').length;

            const round = (amount) => Math.round((amount + Number.EPSILON) * 100) / 100;
            const money = (amount) => amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function syncRemoveButtons() {
                const rows = items.querySelectorAll('[data-invoice-item]');
                rows.forEach((row) => { row.querySelector('[data-remove-invoice-item]').disabled = rows.length <= 1; });
            }

            // Mirrors CompanyRepository::savePackageOffer(), which has the final say.
            function recalculate() {
                let subtotal = 0;
                items.querySelectorAll('[data-invoice-item]').forEach((row) => {
                    const quantity = Math.max(0, Number(row.querySelector('[name$="[quantity]"]').value || 0));
                    const unitPrice = Math.max(0, Number(row.querySelector('[data-invoice-price]').value || 0));
                    const line = round(quantity * round(unitPrice));
                    subtotal += line;
                    row.querySelector('[data-line-total]').textContent = money(line);
                });
                subtotal = round(subtotal);
                const discount = Math.min(subtotal, Math.max(0, Number(document.getElementById('discount').value || 0)));
                const total = round(subtotal - discount);
                const load = document.getElementById('max_load_kw').value;

                document.getElementById('summaryLoad').textContent = load ? load + ' kW' : '—';
                document.getElementById('summarySubtotal').textContent = money(subtotal);
                document.getElementById('summaryDiscount').textContent = '− ' + money(discount);
                document.getElementById('summaryTotal').textContent = money(total);
                document.querySelectorAll('[data-offer-currency]').forEach((label) => { label.textContent = document.getElementById('currency').value; });
                document.getElementById('offerMinimumWarning').hidden = total >= 1000;
                submit.disabled = total < 1000;
            }

            function addRow(description = '', type = '') {
                const holder = document.createElement('template');
                holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', nextIndex++);
                const row = holder.content.firstElementChild;
                row.querySelector('[data-item-description]').value = description;
                row.querySelector('[data-item-type]').value = type;
                items.appendChild(row);
                syncRemoveButtons();
                recalculate();
                return row;
            }

            document.getElementById('addInvoiceItem').addEventListener('click', () => addRow().querySelector('select, input')?.focus());
            document.querySelectorAll('[data-invoice-suggestion]').forEach((button) => {
                button.addEventListener('click', () => addRow(button.dataset.invoiceSuggestion, button.dataset.type).querySelector('[data-invoice-price]')?.focus());
            });
            items.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-invoice-item]');
                if (!remove || items.querySelectorAll('[data-invoice-item]').length <= 1) return;
                remove.closest('[data-invoice-item]').remove();
                syncRemoveButtons();
                recalculate();
            });
            form.addEventListener('input', recalculate);
            form.addEventListener('change', recalculate);
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.textContent = @json(__('company/messages.quote.saving'));
            });

            syncRemoveButtons();
            recalculate();
        });
    </script>
@endsection
