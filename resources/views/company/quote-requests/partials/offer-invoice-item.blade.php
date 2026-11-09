@php
    // Used by the quote offer form (invoice_items) and the package offer form (items).
    $name = $name ?? 'invoice_items';
    $products = $products ?? collect();
    $productId = (string) ($item['product_id'] ?? '');
    // A product picked earlier and since switched off still shows on this line.
    $missingProduct = $productId !== '' && !$products->contains(fn($product) => (string) $product->id === $productId);
@endphp
<div class="offer-invoice-item" data-invoice-item>
    {{-- Own column, so every row's labels and inputs line up. --}}
    <div class="offer-invoice-item-product offer-form-group">
        <label>{{ __('company/messages.package_offers.pick_product') }}</label>
        @if ($products->isNotEmpty() || $missingProduct)
            <select class="form-control offer-invoice-product" name="{{ $name }}[{{ $index }}][product_id]" data-product-picker
                aria-label="{{ __('company/messages.package_offers.pick_product') }}">
                <option value="">{{ __('company/messages.package_offers.custom_item') }}</option>
                @if ($missingProduct)
                    <option value="{{ $productId }}" data-type="{{ $item['type'] ?? '' }}" data-label="{{ $item['description'] ?? '' }}" selected>
                        {{ $item['description'] ?? '#' . $productId }}
                    </option>
                @endif
                @foreach ($products->groupBy('type') as $type => $group)
                    <optgroup label="{{ __('company/messages.common.product_type_' . $type) }}">
                        @foreach ($group as $product)
                            @php $label = $product->name . ($product->model ? ' — ' . $product->model : ''); @endphp
                            <option value="{{ $product->id }}" data-type="{{ $type }}" data-label="{{ $label }}" data-company="{{ $product->company_id }}"
                                @selected($productId === (string) $product->id)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        @else
            {{-- No products yet: every line is typed by hand. --}}
            <select class="form-control offer-invoice-product" disabled aria-label="{{ __('company/messages.package_offers.pick_product') }}">
                <option>{{ __('company/messages.package_offers.custom_item') }}</option>
            </select>
        @endif
    </div>
    <div class="offer-invoice-item-description offer-form-group">
        <label>{{ __('company/messages.quote.item_description') }}</label>
        <input type="hidden" name="{{ $name }}[{{ $index }}][type]" value="{{ $item['type'] ?? '' }}" data-item-type>
        <input class="form-control" name="{{ $name }}[{{ $index }}][description]" type="text" minlength="2" maxlength="255"
            value="{{ $item['description'] ?? '' }}" placeholder="{{ __('company/messages.quote.item_placeholder') }}" aria-label="{{ __('company/messages.quote.item_description_label') }}"
            required data-invoice-required data-item-description>
    </div>
    <div class="offer-invoice-item-quantity offer-form-group">
        <label>{{ __('company/messages.quote.qty') }}</label>
        <input class="form-control" name="{{ $name }}[{{ $index }}][quantity]" type="number" min="0.01" max="10000" step="0.01"
            value="{{ $item['quantity'] ?? 1 }}" aria-label="{{ __('company/messages.quote.quantity') }}" required data-invoice-required>
    </div>
    <div class="offer-invoice-item-price offer-form-group">
        <label>{{ __('company/messages.quote.unit_price') }}</label>
        <input class="form-control" name="{{ $name }}[{{ $index }}][unit_price]" type="number" min="0" step="0.01"
            value="{{ $item['unit_price'] ?? '' }}" placeholder="0.00" aria-label="{{ __('company/messages.quote.unit_price') }}" required data-invoice-required
            data-invoice-price>
    </div>
    <div class="offer-invoice-item-total"><span>{{ __('company/messages.quote.line_total') }}</span><strong data-line-total>0.00</strong></div>
    <button type="button" class="offer-invoice-remove" data-remove-invoice-item title="{{ __('company/messages.quote.remove_item') }}" aria-label="{{ __('company/messages.quote.remove_invoice_item') }}">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
    </button>
</div>
