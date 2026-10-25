<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#182634">
    <title>Reset password · SunXFinder</title>
    <link rel="icon" type="image/png" href="{{ asset('admin/images/favicon-64.png') }}">
    <link rel="stylesheet" href="{{ asset_v('admin/css/admin.css') }}">
</head>

<body class="login-body">
    <main class="login-shell">
        <section class="login-brand-panel" style="padding-top: 115px;">
            <div class="login-panel-glow login-panel-glow-one"></div>
            <div class="login-panel-glow login-panel-glow-two"></div>

            <div class="login-panel-top">
                <div class="login-profile-pic">
                    <img src="{{ asset('admin/images/profile_pic.png') }}" alt="SunXFinder">
                    <span class="profile-online-dot" title="Secure portal"></span>
                </div>
            </div>

            <div class="login-message">
                <span class="login-kicker" style="font-size:14px;">
                    <i class="kicker-line"></i>
                    {{ $portalLabel ?? 'SUNXFINDER ADMIN PORTAL' }}
                </span>

                <h1>
                    Secure your<br>
                    <span>account access.</span>
                </h1>

                <p>{{ $portalDescription ?? '' }}</p>
            </div>

            <div class="login-feature-row">
                <span class="login-feature-icon">✦</span>
                <span><strong>One-time code</strong><small>Valid for 1 minute and usable once.</small></span>
            </div>
        </section>

        <section class="login-form-panel">
            <div class="login-form-wrap">
                <a href="{{ $loginUrl }}" class="back-link"><span aria-hidden="true">←</span> Back to sign in</a>

                <div class="login-form-heading">
                    <span class="login-kicker">ACCOUNT RECOVERY</span>
                    <h2>Reset your password</h2>
                    <p class="login-subtitle">
                        We emailed a 6-digit code to <strong>{{ $resetEmail }}</strong>.
                        Enter it below with your new password.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="alert-error" role="alert">
                        <span class="alert-error-icon">!</span>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert-success" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ $resetAction }}" class="admin-login-form">
                    @csrf

                    <div class="form-field">
                        <label for="resetEmail">Email</label>
                        <div class="input-with-icon">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2.2" stroke="currentColor"
                                        stroke-width="1.7" />
                                    <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            {{-- Display only: the email comes from the session, not this field. --}}
                            <input id="resetEmail" type="email" value="{{ $resetEmail }}" readonly disabled>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="otp">Verification code</label>
                        <div class="input-with-icon">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 3.5 19 6v5.3c0 4.3-2.8 7.5-7 9.2-4.2-1.7-7-4.9-7-9.2V6l7-2.5Z"
                                        stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                    <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <input id="otp" name="otp" type="text" class="otp-input" inputmode="numeric"
                                pattern="\d{6}" maxlength="6" autocomplete="one-time-code" placeholder="••••••"
                                value="{{ old('otp') }}" required autofocus>
                        </div>
                        <small class="otp-countdown" data-otp-countdown data-expires-at="{{ $expiresAt }}"></small>
                        @error('otp')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">New password</label>
                        <div class="input-with-icon password-input-wrap">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4.5" y="10" width="15" height="10" rx="2.2" stroke="currentColor"
                                        stroke-width="1.7" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" />
                                </svg>
                            </span>
                            <input id="password" name="password" type="password" placeholder="Create a new password"
                                autocomplete="new-password" required>
                            <button type="button" class="show-password" data-toggle-password="#password"
                                aria-label="Show password">Show</button>
                        </div>
                        <small class="form-hint">At least 8 characters with upper and lower case letters, a number and
                            a symbol.</small>
                        @error('password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">Confirm new password</label>
                        <div class="input-with-icon password-input-wrap">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4.5" y="10" width="15" height="10" rx="2.2" stroke="currentColor"
                                        stroke-width="1.7" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" />
                                    <circle cx="12" cy="15" r="1.2" fill="currentColor" />
                                </svg>
                            </span>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                placeholder="Repeat the new password" autocomplete="new-password" required>
                            <button type="button" class="show-password" data-toggle-password="#password_confirmation"
                                aria-label="Show password">Show</button>
                        </div>
                    </div>
                    <br>
                    <button class="btn btn-primary login-submit" type="submit">
                        <span>Reset password</span><span class="login-submit-arrow" aria-hidden="true">→</span>
                    </button>
                </form>

                {{-- Separate form: a new code replaces the old one --}}
                <form method="POST" action="{{ $resendAction }}" class="otp-resend">
                    @csrf
                    <span>Didn't get the code or it expired?</span>
                    <button type="submit" class="link-button">Send a new code</button>
                </form>

                <div class="login-security">
                    <span class="security-shield" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5 19 6v5.3c0 4.3-2.8 7.5-7 9.2-4.2-1.7-7-4.9-7-9.2V6l7-2.5Z"
                                stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span>
                        <strong>{{ $securityTitle ?? 'Secure access' }}</strong>
                        <small>Never share your code. SunXFinder staff will never ask for it.</small>
                    </span>
                </div>

                <div class="login-copyright">
                    <span>© {{ date('Y') }} SunXFinder. All rights reserved.</span>
                </div>
            </div>
        </section>
    </main>
    <script src="{{ asset_v('admin/js/admin.js') }}" defer></script>
</body>

</html>
