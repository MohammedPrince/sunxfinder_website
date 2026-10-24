{{-- Horizontal bar list. Expects $title and $items (label => count); optional $labels (key => display text). --}}
@php
    $items = $items ?? [];
    $total = array_sum($items);
    $max = max(1, $items ? max($items) : 1);
    $labels = $labels ?? [];
@endphp

<div class="report-breakdown">
    <h3>{{ $title }}</h3>

    @forelse ($items as $key => $count)
        @php
            $label = $labels[$key] ?? (preg_match('/^[a-z_]+$/', (string) $key) ? \Illuminate\Support\Str::headline($key) : $key);
        @endphp
        <div class="report-bar-row">
            <span class="report-bar-label" title="{{ $label }}">{{ $label }}</span>
            <span class="report-bar-track" aria-hidden="true"><span style="width: {{ round($count / $max * 100) }}%"></span></span>
            <span class="report-bar-value">
                <strong>{{ number_format($count) }}</strong>
                <small>{{ $total > 0 ? round($count / $total * 100) : 0 }}%</small>
            </span>
        </div>
    @empty
        <p class="report-empty">No data for this period.</p>
    @endforelse
</div>
