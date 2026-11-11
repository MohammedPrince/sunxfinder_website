{{-- Warranty and terms of an offer ($offer: CompanyQuotation), in the page language. Nothing when none were given. --}}
@php
    $termLines = $offer?->termLines() ?? [];
    $validUntil = $offer?->validUntil();
    $isArabic = app()->isLocale('ar');
@endphp
@if ($termLines)
    <dl class="offer-terms-list {{ $class ?? '' }}">
        @foreach ($termLines as $line)
            <div>
                <dt>{{ $isArabic ? $line['ar_label'] : $line['label'] }}</dt>
                <dd>{{ $isArabic ? $line['ar_value'] : $line['value'] }}</dd>
            </div>
        @endforeach
        @if ($validUntil)
            <div class="offer-terms-list-validity">
                <dt></dt>
                <dd>{{ __('company/messages.offer_terms.valid_until', ['date' => $validUntil->translatedFormat('d M Y')]) }}</dd>
            </div>
        @endif
    </dl>
@endif
