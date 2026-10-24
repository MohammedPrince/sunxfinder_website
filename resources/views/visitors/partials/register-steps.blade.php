{{-- "What happens next" strip on the sign-up pages. Expects: $steps (list of [title, text]). --}}
<ol class="provider-steps">
    @foreach ($steps as $index => [$title, $text])
        <li>
            <span class="provider-step-no">{{ $index + 1 }}</span>
            <span>
                <strong>{{ $title }}</strong>
                <small>{{ $text }}</small>
            </span>
        </li>
    @endforeach
</ol>
