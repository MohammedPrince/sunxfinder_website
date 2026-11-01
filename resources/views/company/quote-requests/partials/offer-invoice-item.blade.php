<div class="offer-invoice-item" data-invoice-item>
    <div class="offer-invoice-item-description offer-form-group">
        <label>{{ __('company/messages.quote.item_description') }}</label>
        <input class="form-control" name="invoice_items[{{ $index }}][description]" type="text" minlength="2" maxlength="255"
            value="{{ $item['description'] ?? '' }}" placeholder="{{ __('company/messages.quote.item_placeholder') }}" aria-label="{{ __('company/messages.quote.item_description_label') }}"
            required data-invoice-required>
    </div>
    <div class="offer-invoice-item-quantity offer-form-group">
        <label>{{ __('company/messages.quote.qty') }}</label>
        <input class="form-control" name="invoice_items[{{ $index }}][quantity]" type="number" min="0.01" max="10000" step="0.01"
            value="{{ $item['quantity'] ?? 1 }}" aria-label="{{ __('company/messages.quote.quantity') }}" required data-invoice-required>
    </div>
    <div class="offer-invoice-item-price offer-form-group">
        <label>{{ __('company/messages.quote.unit_price') }}</label>
        <input class="form-control" name="invoice_items[{{ $index }}][unit_price]" type="number" min="0" step="0.01"
            value="{{ $item['unit_price'] ?? '' }}" placeholder="0.00" aria-label="{{ __('company/messages.quote.unit_price') }}" required data-invoice-required
            data-invoice-price>
    </div>
    <div class="offer-invoice-item-total"><span>{{ __('company/messages.quote.line_total') }}</span><strong data-line-total>0.00</strong></div>
    <button type="button" class="offer-invoice-remove" data-remove-invoice-item title="{{ __('company/messages.quote.remove_item') }}" aria-label="{{ __('company/messages.quote.remove_invoice_item') }}">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
    </button>
</div>
