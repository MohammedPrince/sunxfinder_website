{{--
    Collapsible "How this page works" box.
    Expects: $key (unique per page, remembers collapsed state), $title, $intro, $steps (array of strings).
    Optional: $note (short closing tip).
--}}
<details class="page-guide no-print" data-guide="{{ $key }}" open>
    <summary>
        <span class="page-guide-icon" aria-hidden="true">i</span>
        <span class="page-guide-title">{{ $title }}</span>
        <span class="page-guide-toggle" aria-hidden="true"></span>
    </summary>

    <div class="page-guide-body">
        @if (!empty($intro))
            <p>{{ $intro }}</p>
        @endif

        @if (!empty($steps))
            <ol>
                @foreach ($steps as $step)
                    <li>{!! $step !!}</li>
                @endforeach
            </ol>
        @endif

        @if (!empty($note))
            <p class="page-guide-note">{{ $note }}</p>
        @endif
    </div>
</details>
