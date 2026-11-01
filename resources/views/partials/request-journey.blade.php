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
        $project?->status === 'completed' => company_text('shared.journey.finished', 'Finished'),
        $project?->status === 'in_progress' => company_text('shared.journey.percent_done', $project->progress_percent . '% done', ['percent' => $project->progress_percent]),
        $project !== null => company_text('shared.journey.not_started_yet', 'Not started yet'),
        default => company_text('shared.journey.after_acceptance', 'After acceptance'),
    };

    $steps = [
        [
            'title' => company_text('shared.journey.request_sent', 'Request sent'),
            'detail' => $quote->created_at ? company_date($quote->created_at, 'd M Y') : '—',
            'done' => true,
        ],
        [
            'title' => company_text('shared.journey.offers_received', 'Offers received'),
            'detail' => $offersCount > 0
                ? (company_portal() ? trans_choice('company/messages.shared.journey.offers_count', $offersCount) : $offersCount . ' ' . \Illuminate\Support\Str::plural('offer', $offersCount))
                : company_text('shared.journey.none_yet', 'None yet'),
            'done' => $offersCount > 0 || $isAccepted,
        ],
        [
            'title' => company_text('shared.journey.offer_accepted', 'Offer accepted'),
            'detail' => $isAccepted
                ? ($project?->accepted_at ? company_date($project->accepted_at, 'd M Y') : company_text('shared.journey.accepted', 'Accepted'))
                : ($offersCount > 0 ? company_text('shared.journey.customer_deciding', 'Customer deciding') : company_text('shared.journey.waiting', 'Waiting')),
            'done' => $isAccepted,
        ],
        [
            'title' => company_text('shared.journey.installation', 'Installation'),
            'detail' => $installationDetail,
            'done' => $isCompleted,
        ],
        [
            'title' => company_text('shared.journey.completed', 'Completed'),
            'detail' => $project?->completed_at ? company_date($project->completed_at, 'd M Y') : ($isCompleted ? company_text('shared.journey.done', 'Done') : '—'),
            'done' => $isCompleted,
        ],
    ];

    // The first unfinished step is where the request is now.
    $currentIndex = $isClosed ? null : collect($steps)->search(fn($step) => !$step['done']);
@endphp

<section class="request-journey" aria-label="{{ company_text('shared.journey.request_progress', 'Request progress') }}">
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
            {{ $quoteStatus === 'cancelled' ? company_text('shared.journey.closed_cancelled', 'This request was cancelled by the customer and is closed.') : company_text('shared.journey.closed_rejected', 'This request was rejected and is closed.') }}
        </p>
    @endif
</section>
