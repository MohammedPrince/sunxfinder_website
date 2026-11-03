{{-- Read-only stars. Usage: @include('partials.stars', ['value' => 4.3, 'large' => true]) --}}
@php
    $max = $max ?? 5;
    $filled = (int) round((float) ($value ?? 0));
@endphp
<span class="star-rating {{ !empty($large) ? 'star-rating-lg' : '' }}" role="img"
    aria-label="{{ company_text('shared.out_of_stars', number_format((float) ($value ?? 0), 1) . ' out of ' . $max . ' stars', ['value' => number_format((float) ($value ?? 0), 1), 'max' => $max]) }}">
    @for ($i = 1; $i <= $max; $i++)
        <span class="{{ $i <= $filled ? 'on' : '' }}" aria-hidden="true">★</span>
    @endfor
</span>
