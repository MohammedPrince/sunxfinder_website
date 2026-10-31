<div class="offer-invoice-item" data-invoice-item>
    <div class="offer-invoice-item-description offer-form-group">
        <label>Description</label>
        <input class="form-control" name="invoice_items[{{ $index }}][description]" type="text" minlength="2" maxlength="255"
            value="{{ $item['description'] ?? '' }}" placeholder="e.g. 550W solar panel" aria-label="Item description"
            required data-invoice-required>
    </div>
    <div class="offer-invoice-item-quantity offer-form-group">
        <label>Qty</label>
        <input class="form-control" name="invoice_items[{{ $index }}][quantity]" type="number" min="0.01" max="10000" step="0.01"
            value="{{ $item['quantity'] ?? 1 }}" aria-label="Quantity" required data-invoice-required>
    </div>
    <div class="offer-invoice-item-price offer-form-group">
        <label>Unit price</label>
        <input class="form-control" name="invoice_items[{{ $index }}][unit_price]" type="number" min="0" step="0.01"
            value="{{ $item['unit_price'] ?? '' }}" placeholder="0.00" aria-label="Unit price" required data-invoice-required
            data-invoice-price>
    </div>
    <div class="offer-invoice-item-total"><span>Line total</span><strong data-line-total>0.00</strong></div>
    <button type="button" class="offer-invoice-remove" data-remove-invoice-item title="Remove item" aria-label="Remove invoice item">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
    </button>
</div>
