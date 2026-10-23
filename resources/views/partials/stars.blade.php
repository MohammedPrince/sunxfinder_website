{{-- Read-only stars. Usage: @include('partials.stars', ['value' => 4.3, 'large' => true]) --}}
@php
    $max = $max ?? 5;
    $filled = (int) round((float) ($value ?? 0));
@endphp
<span class="star-rating {{ !empty($large) ? 'star-rating-lg' : '' }}" role="img"
    aria-label="{{ number_format((float) ($value ?? 0), 1) }} out of {{ $max }} stars">
    @for ($i = 1; $i <= $max; $i++)
        <span class="{{ $i <= $filled ? 'on' : '' }}" aria-hidden="true">★</span>
    @endfor
</span>
