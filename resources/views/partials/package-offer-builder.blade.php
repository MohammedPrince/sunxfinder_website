{{--
    Package offer builder, shared by the company panel (Offers) and the admin customer campaign.
    Fields are posted as package[...] and validated with PackageOffer::rules().

    @param \App\Models\PackageOffer|null $base      offer to edit or copy (null: new)
    @param \Illuminate\Support\Collection $products  active products (with company_id); the admin page
                                                     shows all visible companies' and filters by company
    Needs partials.package-offer-builder-script once on the page.
--}}
@php
    $base = $base ?? null;
    $products = $products ?? collect();
    $t = fn(string $key, array $replace = []) => __('company/messages.package_offers.' . $key, $replace);
    $items = array_values(old('package.items', $base?->items ?? [
        ['type' => 'solar_panel', 'description' => __('company/messages.quote.sug_panels'), 'quantity' => 6, 'unit_price' => ''],
        ['type' => 'inverter', 'description' => __('company/messages.quote.sug_inverter'), 'quantity' => 1, 'unit_price' => ''],
        ['type' => 'battery', 'description' => __('company/messages.quote.sug_batteries'), 'quantity' => 2, 'unit_price' => ''],
        ['type' => 'other', 'description' => __('company/messages.quote.sug_installation'), 'quantity' => 1, 'unit_price' => ''],
    ]));
    $currency = old('package.currency', $base?->currency ?? 'SDG');
    // A copied offer starts without an end date; an edited one keeps its own.
    $expiresAt = old('package.expires_at', $base?->exists && !$base->isLocked() ? $base->expires_at?->format('Y-m-d\TH:i') : null);
@endphp

<div class="pkg-builder" data-package-builder>
    <div class="pkg-grid">
        <div class="pkg-field">
            <label for="package_title_en">{{ $t('title_en') }} <b>*</b></label>
            <input class="form-control" id="package_title_en" name="package[title_en]" type="text" maxlength="150"
                value="{{ old('package.title_en', $base?->title_en) }}" placeholder="{{ $t('title_placeholder') }}" data-pkg-title-en>
            @error('package.title_en')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="pkg-field">
            <label for="package_title_ar">{{ $t('title_ar') }}</label>
            <input class="form-control" id="package_title_ar" name="package[title_ar]" type="text" maxlength="150" dir="rtl" lang="ar"
                value="{{ old('package.title_ar', $base?->title_ar) }}" placeholder="مثال: منظومة 5 كيلوواط مع التركيب" data-pkg-title-ar>
        </div>
        <div class="pkg-field">
            <label for="package_description_en">{{ $t('description_en') }}</label>
            <textarea class="form-control" id="package_description_en" name="package[description_en]" rows="3" maxlength="2000"
                placeholder="{{ $t('description_placeholder') }}">{{ old('package.description_en', $base?->description_en) }}</textarea>
        </div>
        <div class="pkg-field">
            <label for="package_description_ar">{{ $t('description_ar') }}</label>
            <textarea class="form-control" id="package_description_ar" name="package[description_ar]" rows="3" maxlength="2000" dir="rtl" lang="ar"
                placeholder="ماذا يشمل العرض: الضمان، التركيب، مدة التنفيذ...">{{ old('package.description_ar', $base?->description_ar) }}</textarea>
        </div>
    </div>

    <div class="pkg-grid pkg-grid-3">
        <div class="pkg-field">
            <label for="package_max_load_kw">{{ $t('max_load') }} (kW) <b>*</b></label>
            <input class="form-control" id="package_max_load_kw" name="package[max_load_kw]" type="number" min="0.1" max="1000" step="0.1"
                value="{{ old('package.max_load_kw', $base ? $base->maxLoadLabel() : '') }}" placeholder="5" data-pkg-load>
            <small class="pkg-hint">{{ $t('max_load_hint') }}</small>
            @error('package.max_load_kw')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="pkg-field">
            <label for="package_currency">{{ __('company/messages.quote.currency') }} <b>*</b></label>
            <select class="select-control form-control" id="package_currency" name="package[currency]" data-pkg-currency>
                @foreach (['SDG', 'USD'] as $code)
                    <option value="{{ $code }}" @selected($currency === $code)>{{ $code }}</option>
                @endforeach
            </select>
        </div>
        <div class="pkg-field">
            <label for="package_expires_at">{{ $t('valid_until') }}</label>
            <input class="form-control" id="package_expires_at" name="package[expires_at]" type="datetime-local" value="{{ $expiresAt }}" data-pkg-expires>
            <small class="pkg-hint">{{ $t('valid_until_hint') }}</small>
            @error('package.expires_at')<small class="field-error">{{ $message }}</small>@enderror
        </div>
    </div>

    <div class="pkg-items-head">
        <div>
            <strong>{{ $t('items') }}</strong>
            <span>{{ $t('items_sub') }}</span>
        </div>
        <button type="button" class="btn btn-light" data-pkg-add-item>{{ __('company/messages.quote.add_item') }}</button>
    </div>
    <small class="pkg-hint" data-pkg-no-products @if ($products->isNotEmpty()) hidden @endif>{{ $t('no_products') }}</small>

    <div class="offer-invoice-items" data-pkg-items>
        @foreach ($items as $item)
            @include('company.quote-requests.partials.offer-invoice-item', ['name' => 'package[items]', 'index' => $loop->index, 'item' => $item, 'products' => $products])
        @endforeach
    </div>
    <template data-pkg-item-template>
        @include('company.quote-requests.partials.offer-invoice-item', ['name' => 'package[items]', 'index' => '__INDEX__', 'item' => [], 'products' => $products])
    </template>
    @error('package.items')<small class="field-error">{{ $message }}</small>@enderror

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
            <button type="button" data-pkg-suggestion="{{ $suggestion }}" data-type="{{ $type }}">{{ $suggestion }}</button>
        @endforeach
    </div>

    <div class="pkg-totals">
        <div class="pkg-field">
            <label for="package_discount">{{ __('company/messages.quote.discount') }}</label>
            <input class="form-control" id="package_discount" name="package[discount]" type="number" min="0" step="0.01"
                value="{{ old('package.discount', $base?->discount ?? 0) }}" data-pkg-discount>
        </div>
        <div><span>{{ __('company/messages.quote.subtotal') }}</span><strong data-pkg-subtotal>0.00</strong></div>
        <div><span>{{ __('company/messages.quote.discount') }}</span><strong data-pkg-discount-total>0.00</strong></div>
        <div class="pkg-total"><span>{{ $t('customer_pays') }}</span><strong><span data-pkg-total>0.00</span> <small data-pkg-currency-label>{{ $currency }}</small></strong></div>
    </div>
    <small class="pkg-minimum" data-pkg-minimum hidden>{{ __('company/messages.quote.minimum_warning') }}</small>
</div>
