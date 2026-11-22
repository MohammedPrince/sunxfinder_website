{{--
    "How to use the system" journey: opens once, on the company's first sign-in
    (Company\AuthController::login sets the session flag). Text: company/manual.php.
--}}
<dialog class="admin-dialog journey-dialog" id="companyJourneyDialog" aria-labelledby="companyJourneyTitle">
    <div class="admin-dialog-head">
        <div>
            <h2 id="companyJourneyTitle">{{ __('company/manual.journey_title') }}</h2>
            <p>{{ __('company/manual.intro_body') }}</p>
        </div>
        <button type="button" class="admin-dialog-close" data-journey-close aria-label="{{ __('messages.Close') }}">×</button>
    </div>

    <div class="admin-dialog-body">
        <h3 class="manual-label">{{ __('company/manual.flow_title') }}</h3>
        <ol class="manual-flow">
            @foreach (__('company/manual.flow') as [$label, $text])
                <li>
                    <span class="manual-flow-number">{{ $loop->iteration }}</span>
                    <strong>{{ $label }}</strong>
                    <small>{{ $text }}</small>
                </li>
            @endforeach
        </ol>

        <h3 class="manual-label">{{ __('company/manual.journey_start_title') }}</h3>
        <ol class="journey-start">
            @foreach (__('company/manual.journey_start') as [$label, $text, $routeName])
                <li>
                    <a href="{{ route($routeName) }}">
                        <span class="manual-flow-number">{{ $loop->iteration }}</span>
                        <span class="journey-start-copy">
                            <strong>{{ $label }}</strong>
                            <small>{{ $text }}</small>
                        </span>
                        <span class="topbar-item-arrow" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ol>

        <p class="journey-note">{{ __('company/manual.journey_note') }}</p>
    </div>

    <div class="admin-dialog-actions">
        <a class="btn btn-light" href="{{ route('company.manual') }}">{{ __('company/manual.journey_manual') }}</a>
        <button type="button" class="btn btn-primary" data-journey-close autofocus>{{ __('company/manual.journey_done') }}</button>
    </div>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var dialog = document.getElementById('companyJourneyDialog');
        if (!dialog || typeof dialog.showModal !== 'function') return;

        dialog.querySelectorAll('[data-journey-close]').forEach(function (button) {
            button.addEventListener('click', function () { dialog.close(); });
        });
        dialog.showModal();
    });
</script>
