{{-- Month-over-month change badge; $change is a percentage or null when last month had no records. --}}
@if ($change === null)
    <b class="gray-text">New this month</b>
@elseif ($change >= 0)
    <b class="positive">↗ {{ $change }}%</b> vs last month
@else
    <b class="negative">↘ {{ abs($change) }}%</b> vs last month
@endif
