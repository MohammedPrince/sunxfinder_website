<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#182634">
    <title>{{ $loginTitle ?? 'Sign in' }} · SunXFinder</title>
    <link rel="icon" type="image/png" href="{{ asset('admin/images/sun.png') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/admin.css') }}">
</head>

<body class="login-body">
    <main class="login-shell">
        <section class="login-brand-panel" style="padding-top: 115px;">
            <div class="login-panel-glow login-panel-glow-one"></div>
            <div class="login-panel-glow login-panel-glow-two"></div>

            <div class="login-panel-top">
                <div class="login-profile-pic">
                    <img src="{{ asset('admin/images/profile_pic.png') }}" alt="SunXFinder administrator">
                    <span class="profile-online-dot" title="Admin portal"></span>
                </div>

            </div>

            <div class="login-message">
                <span class="login-kicker" style="font-size:14px;">
                    <i class="kicker-line"></i>
                    {{ $portalLabel ?? 'SUNXFINDER ADMIN PORTAL' }}
                </span>

                <h1>
                    Powering better<br>
                    <span>solar connections.</span>
                </h1>

                <p>
                    {{ $portalDescription ?? 'Manage solar providers, customer enquiries, and the connections that move clean energy forward.' }}
                </p>
            </div>

            <div class="login-feature-row">
                <span class="login-feature-icon">✦</span>
                <span><strong>Built for your marketplace</strong><small>Clear insights. Smarter
                        management.</small></span>
            </div>


        </section>

        <section class="login-form-panel">
            <div class="login-form-wrap">
                <a href="{{ url('/') }}" class="back-link"><span aria-hidden="true">←</span> Back to website</a>

                <div class="login-form-heading">
                    <span class="login-kicker">WELCOME BACK</span>
                    <h2>Sign in to your account</h2>
                    <p class="login-subtitle">Enter your administrator credentials to continue.</p>
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


                <form method="POST" action="{{ $loginAction ?? route('admin.login.submit') }}"
                    class="admin-login-form">
                    @csrf

                    <div class="form-field">
                        <label for="username">
                            {{ $loginFieldLabel ?? 'Username' }}
                        </label>

                        <div class="input-with-icon">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="4" stroke="currentColor"
                                        stroke-width="1.7" />
                                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" />
                                </svg>
                            </span>

                            <input id="username" name="username" type="text" value="{{ old('username') }}"
                                placeholder="{{ $loginPlaceholder ?? 'Your username' }}" autocomplete="username"
                                required autofocus>
                        </div>

                        @error('username')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>


                    <div class="form-field">
                        <label for="password">Password</label>
                        <div class="input-with-icon password-input-wrap">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4.5" y="10" width="15" height="10" rx="2.2"
                                        stroke="currentColor" stroke-width="1.7" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" />
                                    <circle cx="12" cy="15" r="1.2" fill="currentColor" />
                                </svg>
                            </span>
                            <input id="password" name="password" type="password" placeholder="Enter your password"
                                autocomplete="current-password" required>
                            <button type="button" class="show-password" data-toggle-password="#password"
                                aria-label="Show password">Show</button>
                        </div>
                        @error('password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="login-options">

                        <a href="#" data-demo-toast="Connect password reset to your authentication flow.">Forgot
                            password?</a>
                    </div>

                    <button class="btn btn-primary login-submit" type="submit">
                        <span>Sign in</span><span class="login-submit-arrow" aria-hidden="true">→</span>
                    </button>
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
                        <strong>{{ $securityTitle ?? 'Secure administrator access' }}</strong>
                        <small>{{ $securityMessage ?? 'Your account is protected by your sign-in session.' }}</small>
                    </span>
                </div>

                <div class="login-copyright">
                    <span>© {{ date('Y') }} SunXFinder. All rights reserved.</span>
                    <span class="copyright-dot"></span>
                    <span>Admin Portal</span>
                </div>
            </div>
        </section>
    </main>
    <script src="{{ asset('admin/js/admin.js') }}" defer></script>
</body>

</html>
