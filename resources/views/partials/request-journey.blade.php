{{--
    Five-step tracker showing where a quote request stands:
    Request sent → Offers received → Offer accepted → Installation → Completed.
    Expects: $quote. Optional: $offersCount (int), $project (Project|null).
--}}
@php
    $offersCount = (int) ($offersCount ?? 0);
    $project = $project ?? null;
    $quoteStatus = $quote->status;

    $isClosed = in_array($quoteStatus, ['cancelled', 'rejected'], true);
    $isAccepted = in_array($quoteStatus, ['accepted', 'completed'], true) || $project !== null;
    $isCompleted = $quoteStatus === 'completed' || $project?->status === 'completed';

    $installationDetail = match (true) {
        $project?->status === 'completed' => 'Finished',
        $project?->status === 'in_progress' => $project->progress_percent . '% done',
        $project !== null => 'Not started yet',
        default => 'After acceptance',
    };

    $steps = [
        [
            'title' => 'Request sent',
            'detail' => $quote->created_at?->format('d M Y') ?? '—',
            'done' => true,
        ],
        [
            'title' => 'Offers received',
            'detail' => $offersCount > 0
                ? $offersCount . ' ' . \Illuminate\Support\Str::plural('offer', $offersCount)
                : 'None yet',
            'done' => $offersCount > 0 || $isAccepted,
        ],
        [
            'title' => 'Offer accepted',
            'detail' => $isAccepted
                ? ($project?->accepted_at?->format('d M Y') ?? 'Accepted')
                : ($offersCount > 0 ? 'Customer deciding' : 'Waiting'),
            'done' => $isAccepted,
        ],
        [
            'title' => 'Installation',
            'detail' => $installationDetail,
            'done' => $isCompleted,
        ],
        [
            'title' => 'Completed',
            'detail' => $project?->completed_at?->format('d M Y') ?? ($isCompleted ? 'Done' : '—'),
            'done' => $isCompleted,
        ],
    ];

    // The first unfinished step is where the request is now.
    $currentIndex = $isClosed ? null : collect($steps)->search(fn($step) => !$step['done']);
@endphp

<section class="request-journey" aria-label="Request progress">
    <ol>
        @foreach ($steps as $index => $step)
            <li class="{{ $step['done'] ? 'is-done' : '' }} {{ $index === $currentIndex ? 'is-current' : '' }}"
                @if ($index === $currentIndex) aria-current="step" @endif>
                <span class="journey-marker">{{ $step['done'] ? '✓' : $index + 1 }}</span>
                <span class="journey-copy">
                    <strong>{{ $step['title'] }}</strong>
                    <small>{{ $step['detail'] }}</small>
                </span>
            </li>
        @endforeach
    </ol>

    @if ($isClosed)
        <p class="journey-closed">
            This request was {{ $quoteStatus === 'cancelled' ? 'cancelled by the customer' : 'rejected' }} and is closed.
        </p>
    @endif
</section>
