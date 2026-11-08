@php
    $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $peakKw = \App\Models\PackageOffer::loadToKw($quote->peak_load);
@endphp
<section class="panel package-offer-banner no-print">
    <div class="package-offer-banner-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z" />
            <circle cx="7.5" cy="7.5" r="1.5" />
        </svg>
    </div>
    <div class="package-offer-banner-copy">
        <span class="offer-eyebrow">{{ __('company/messages.package_offers.banner_eyebrow') }}</span>
        <h3>
            <a href="{{ route('company.offers.show', $packageOffer->id) }}">{{ $packageOffer->title($lang) }}</a>
        </h3>
        <p>
            {{ __('company/messages.package_offers.banner_load', ['max' => $packageOffer->maxLoadLabel(), 'peak' => $quote->peak_load ?: '—']) }}
            · {{ number_format((float) $packageOffer->price, 2) }} {{ $packageOffer->currency }}
        </p>
        <p>{{ __('company/messages.package_offers.banner_done') }}</p>
        @if ($peakKw !== null && $peakKw > (float) $packageOffer->max_load_kw)
            <p class="field-error">{{ __('company/messages.package_offers.banner_over') }}</p>
        @endif
    </div>
</section>
