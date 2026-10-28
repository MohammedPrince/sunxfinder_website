@extends('visitors.layouts.app')
@section('title', __('messages.SunXFinder - Create your account'))

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $appLinks = $appLinks ?? [];

    $cities = [
        'Khartoum' => 'الخرطوم',
        'Port Sudan' => 'بورتسودان',
        'Atbara' => 'عطبرة',
        'Wad Madani' => 'مدني',
        'Merowe' => 'مروي',
        'Kassala' => 'كسلا',
        'Al Qadarif' => 'القضارف',
        'Dongola' => 'دنقلا',
        'Halfa' => 'حلفا',
    ];
@endphp

@section('content')
    <div class="provider-register-page">
        <div class="provider-register-container">
            <header class="provider-register-heading">
                <a href="{{ route('home', ['lang' => $locale]) }}" class="provider-back-link">
                    <span aria-hidden="true">{{ $isArabic ? '→' : '←' }}</span> {{ __('messages.Back to home') }}
                </a>

                <h1>{{ __('messages.Create your customer account') }}</h1>
                <p>{{ __('messages.One free account to find solar companies, compare their offers and connect with the best one.') }}</p>
            </header>

            @include('visitors.partials.register-steps', ['steps' => [
                [__('messages.Create your account'), __('messages.Takes about 2 minutes.')],
                [__('messages.We approve it'), __('messages.We check your details and email you.')],
                [__('messages.Get offers in the app'), __('messages.Send your request and compare offers.')],
            ]])

            @if (session('success'))
                {{-- Done: no form, just what to do next --}}
                <section class="provider-success" role="status">
                    <span class="provider-success-icon" aria-hidden="true">✓</span>
                    <h2>{{ __('messages.Your account was created') }}</h2>
                    <p>{{ session('success') }}</p>
                    <p>{{ __('messages.We will email you when your account is approved. Then open the SunXFinder app and log in with your username and password.') }}</p>

                    @if ($appLinks)
                        <p>{{ __('messages.Download the SunXFinder app:') }}</p>
                        <div class="provider-success-actions">
                            @isset($appLinks['android'])
                                <a class="provider-submit-button" href="{{ $appLinks['android'] }}" target="_blank" rel="noopener">{{ __('messages.Get it on') }} Google Play</a>
                            @endisset
                            @isset($appLinks['ios'])
                                <a class="provider-submit-button" href="{{ $appLinks['ios'] }}" target="_blank" rel="noopener">{{ __('messages.Download on the') }} App Store</a>
                            @endisset
                        </div>
                    @endif

                    <a class="provider-login-link-inline" href="{{ route('home', ['lang' => $locale]) }}">{{ __('messages.Back to home') }}</a>
                </section>
            @else
                @if ($errors->any())
                    <div class="provider-alert provider-alert-error" role="alert">
                        @if ($errors->has('registration'))
                            {{ $errors->first('registration') }}
                        @else
                            <strong>{{ __('messages.Please check the highlighted fields.') }}</strong>
                        @endif
                    </div>
                @endif

                <form action="{{ route('customer.register.store', ['lang' => $locale]) }}" method="POST"
                    class="provider-register-form" data-register-form>
                    @csrf

                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">1</span>
                            <div>
                                <h2>{{ __('messages.Personal information') }}</h2>
                                <p>{{ __('messages.Enter your name and contact details.') }}</p>
                            </div>
                        </div>

                        <div class="provider-form-grid">
                            <div class="provider-field">
                                <label for="first_name">{{ __('messages.First name') }} <b>*</b></label>
                                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="{{ __('messages.First name') }}"
                                    minlength="3" maxlength="15" autocomplete="given-name" required data-capitalize
                                    @class(['is-invalid' => $errors->has('first_name')])>
                                @error('first_name')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="second_name">{{ __('messages.Second name') }} <b>*</b></label>
                                <input type="text" id="second_name" name="second_name" value="{{ old('second_name') }}" placeholder="{{ __('messages.Second name') }}"
                                    minlength="3" maxlength="15" autocomplete="additional-name" required data-capitalize
                                    @class(['is-invalid' => $errors->has('second_name')])>
                                @error('second_name')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="third_name">{{ __('messages.Third name') }} <b>*</b></label>
                                <input type="text" id="third_name" name="third_name" value="{{ old('third_name') }}" placeholder="{{ __('messages.Third name') }}"
                                    minlength="3" maxlength="15" autocomplete="family-name" required data-capitalize
                                    @class(['is-invalid' => $errors->has('third_name')])>
                                @error('third_name')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="gender">{{ __('messages.Gender') }} <b>*</b></label>
                                <select id="gender" name="gender" required @class(['is-invalid' => $errors->has('gender')])>
                                    <option value="" disabled @selected(!old('gender'))>{{ __('messages.Select gender') }}</option>
                                    <option value="male" @selected(old('gender') === 'male')>{{ __('messages.Male') }}</option>
                                    <option value="female" @selected(old('gender') === 'female')>{{ __('messages.Female') }}</option>
                                </select>
                                @error('gender')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="phone">{{ __('messages.Phone number') }} <b>*</b></label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                                    minlength="10" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                                    autocomplete="tel" placeholder="0912345678" data-digits-only
                                    @class(['is-invalid' => $errors->has('phone')])>
                                <small>{{ __('messages.10 digits, for example 0912345678.') }}</small>
                                @error('phone')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="email">{{ __('messages.Email address') }} <b>*</b></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="100"
                                    autocomplete="email" required placeholder="you@example.com"
                                    @class(['is-invalid' => $errors->has('email')])>
                                <small>{{ __('messages.We send your account updates here.') }}</small>
                                @error('email')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">2</span>
                            <div>
                                <h2>{{ __('messages.Address information') }}</h2>
                                <p>{{ __('messages.Companies use it to plan the site visit and the installation.') }}</p>
                            </div>
                        </div>

                        <div class="provider-form-grid">
                            <div class="provider-field provider-field-full">
                                <label for="city">{{ __('messages.City') }} <b>*</b></label>
                                <select id="city" name="city" required @class(['is-invalid' => $errors->has('city')])>
                                    <option value="" disabled @selected(!old('city'))>{{ __('messages.Select your city') }}</option>
                                    @foreach ($cities as $english => $arabic)
                                        <option value="{{ $english }}" @selected(old('city') === $english)>
                                            {{ $isArabic ? $arabic : $english }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('city')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field provider-field-full">
                                <label for="address_details">{{ __('messages.District, neighborhood and street') }} <b>*</b></label>
                                <textarea id="address_details" name="address_details" rows="3" maxlength="1000" required
                                    placeholder="{{ __('messages.Example: Bahri, Kobar, Square 5, Street 12') }}"
                                    @class(['is-invalid' => $errors->has('address_details')])>{{ old('address_details') }}</textarea>
                                @error('address_details')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">3</span>
                            <div>
                                <h2>{{ __('messages.Login details') }}</h2>
                                <p>{{ __('messages.You will use these to log in to the SunXFinder app.') }}</p>
                            </div>
                        </div>

                        <div class="provider-form-grid">
                            <div class="provider-field provider-field-full">
                                <label for="username">{{ __('messages.Username') }} <b>*</b></label>
                                <input type="text" id="username" name="username" value="{{ old('username') }}"
                                    minlength="6" maxlength="15" pattern="[A-Za-z0-9_]{6,15}" autocomplete="username" placeholder="{{ __('messages.username') }}"
                                    required  data-latin-only data-latin-message="{{ __('messages.Please use English letters only for your username.') }}"
                                    @class(['is-invalid' => $errors->has('username')])>
                                <small>{{ __('messages.6 to 15 English letters, numbers or _ (no spaces).') }}</small>
                                <small class="provider-field-error" data-latin-error hidden></small>
                                @error('username')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Must match the "min:5" password rule in WebsiteController::storeCustomer --}}
                            @include('visitors.partials.password-fields', ['minLength' => 5])
                        </div>
                        <p class="provider-login-link" role="note">
                            {{ __('messages.After your account is approved, remember to log in using the SunXFinder app with the username and password you created here.') }}
                        </p>
                        @if (!empty($appLinks))
                            {{-- <div class="provider-success-actions">
                                @isset($appLinks['android'])
                                    <a class="provider-submit-button" href="{{ $appLinks['android'] }}" target="_blank" rel="noopener">{{ __('messages.Get it on') }} Google Play</a>
                                @endisset
                                @isset($appLinks['ios'])
                                    <a class="provider-submit-button" href="{{ $appLinks['ios'] }}" target="_blank" rel="noopener">{{ __('messages.Download on the') }} App Store</a>
                                @endisset
                            </div> --}}
                        @endif
                    </section>

                    <footer class="provider-register-footer">
                        <p>
                            {{ __('messages.By creating an account, you agree to our') }}
                            <a href="{{ route('terms', ['lang' => $locale]) }}" target="_blank">{{ __('messages.Terms') }}</a>
                            {{ __('messages.and') }}
                            <a href="{{ route('privacy-policy', ['lang' => $locale]) }}" target="_blank">{{ __('messages.Privacy') }}</a>.
                        </p>
                        <button type="submit" class="provider-submit-button">{{ __('messages.Create customer account') }}</button>
                        <p class="provider-login-link">
                            {{ __('messages.Are you a solar company?') }}
                            <a href="{{ route('company.register', ['lang' => $locale]) }}">{{ __('messages.Join as a company') }}</a>
                        </p>
                    </footer>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset_v('visitors/js/register.js') }}" defer></script>
@endsection
