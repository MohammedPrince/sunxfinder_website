{{-- One "extra benefit" row of the offer's warranty and terms. --}}
<div class="offer-terms-extra" data-offer-extra>
    <input class="form-control" type="text" maxlength="150" name="terms[extras][{{ $index }}][en]"
        value="{{ $extra['en'] ?? '' }}" placeholder="{{ __('company/messages.offer_terms.extra_en_placeholder') }}"
        aria-label="{{ __('company/messages.offer_terms.extra') }} — {{ __('company/messages.offer_terms.extra_en') }}">
    <input class="form-control" type="text" maxlength="150" dir="rtl" lang="ar" name="terms[extras][{{ $index }}][ar]"
        value="{{ $extra['ar'] ?? '' }}" placeholder="مثال: دعم هاتفي مجاني على مدار الساعة"
        aria-label="{{ __('company/messages.offer_terms.extra') }} — {{ __('company/messages.offer_terms.extra_ar') }}">
    <button type="button" class="offer-invoice-remove" data-remove-offer-extra
        title="{{ __('company/messages.offer_terms.remove_extra') }}" aria-label="{{ __('company/messages.offer_terms.remove_extra') }}">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
    </button>
</div>
