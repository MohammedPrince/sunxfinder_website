@extends('visitors.layouts.app')
@section('title', __('messages.SunXFinder - Create Company Account'))

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

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
            <div class="provider-register-heading">
                <a href="{{ route('home', ['lang' => $locale]) }}" class="provider-back-link">
                    <span aria-hidden="true">{{ $isArabic ? '→' : '←' }}</span> {{ __('messages.Back to home') }}
                </a>

                <h1>{{ __('messages.Create your company account') }}</h1>
                <p>{{ __('messages.Join SunXFinder and receive requests from customers who are ready to go solar.') }}</p>
            </div>

            @include('visitors.partials.register-steps', ['steps' => [
                [__('messages.Register your company'), __('messages.Takes about 5 minutes.')],
                [__('messages.We review it'), __('messages.Our team checks your details and activates your account.')],
                [__('messages.Receive requests'), __('messages.Send offers to customers and win projects.')],
            ]])

            @if (session('success'))
                <section class="provider-success" role="status">
                    <span class="provider-success-icon" aria-hidden="true">✓</span>
                    <h2>{{ __('messages.Thank you! Your company was submitted') }}</h2>
                    <p>{{ session('success') }}</p>
                    <p>{{ __('messages.Our team will review your details and email you when your account is active. Then you can sign in to your company dashboard.') }}</p>
                    <div class="provider-success-actions">
                        <a class="provider-submit-button" href="{{ route('company.login') }}">{{ __('messages.Company login') }}</a>
                    </div>
                    <a class="provider-login-link-inline" href="{{ route('home', ['lang' => $locale]) }}">{{ __('messages.Back to home') }}</a>
                </section>
            @else
                @if (session('error'))
                    <div class="provider-alert provider-alert-error" role="alert">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="provider-alert provider-alert-error" role="alert">
                        <strong>{{ __('messages.Please check the highlighted fields.') }}</strong>
                        @if ($errors->hasAny(['description', 'ar_description']))
                            <ul>
                                @foreach (['description', 'ar_description'] as $field)
                                    @error($field)<li>{{ $message }}</li>@enderror
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <form action="{{ route('company.register.store', ['lang' => $locale]) }}" method="POST"
                    class="provider-register-form" enctype="multipart/form-data" data-register-form>
                    @csrf

                    {{-- 1. Company information --}}
                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">1</span>
                            <div>
                                <h2>{{ __('messages.Company information') }}</h2>
                                <p>{{ __('messages.Tell customers about your solar business.') }}</p>
                            </div>
                        </div>

                        <div class="provider-form-grid">
                            <div class="provider-field provider-field-full">
                                <label for="name">{{ __('messages.Company name') }} <b>*</b></label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" minlength="10"
                                    maxlength="200" required autocomplete="organization" dir="auto"
                                    placeholder="Sunshine Solar Solutions"
                                    @class(['is-invalid' => $errors->has('name')])>
                                <small>{{ __('messages.Your registered name in English or Arabic, 10 to 200 characters.') }}</small>
                                @error('name')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
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

                            <div class="provider-field">
                                <label for="phone">{{ __('messages.Phone number') }} <b>*</b></label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                                    minlength="10" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                                    autocomplete="tel" placeholder="0912345678"  data-digits-only
                                    @class(['is-invalid' => $errors->has('phone')])>
                                <small>{{ __('messages.10 digits, for example 0912345678.') }}</small>
                                @error('phone')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="year_of_exp">{{ __('messages.Years of experience') }} <b>*</b></label>
                                <input type="number" id="year_of_exp" name="year_of_exp" value="{{ old('year_of_exp') }}" placeholder="{{ __('messages.Years of experience') }}"
                                    min="1" max="100" step="1" required inputmode="numeric"
                                    @class(['is-invalid' => $errors->has('year_of_exp')])>
                                @error('year_of_exp')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="number_of_projects">{{ __('messages.Projects done') }} <b>*</b></label>
                                <input type="number" id="number_of_projects" name="number_of_projects"
                                    value="{{ old('number_of_projects') }}" min="1" step="1" required inputmode="numeric" placeholder="{{  __('messages.Projects done') }}"
                                    @class(['is-invalid' => $errors->has('number_of_projects')])>
                                <small>{{ __('messages.Solar projects your company has completed so far.') }}</small>
                                @error('number_of_projects')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field provider-field-full">
                                <label for="social_media">{{ __('messages.Social Media Links') }}</label>
                                <textarea name="social_media" id="social_media" rows="2" maxlength="5000" 
                                    placeholder="https://facebook.com/your-company"
                                    @class(['is-invalid' => $errors->has('social_media')])>{{ old('social_media') }}</textarea>
                                <small>{{ __('messages.Optional. One link per line: Facebook, Instagram, LinkedIn or your website.') }}</small>
                                @error('social_media')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </section>

                    {{-- 2. Services --}}
                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">2</span>
                            <div>
                                <h2>{{ __('messages.Solar services') }}</h2>
                                <p>{{ __('messages.Customers see these on your company page. Separate services with commas.') }}</p>
                            </div>
                        </div>

                        <div class="provider-form-grid">
                            <div class="provider-field">
                                <label for="services">{{ __('messages.Services in English') }} <b>*</b></label>
                                <textarea name="services" id="services" rows="3" required maxlength="5000" 
                                    placeholder="Home solar, Hybrid systems, Water pumps"
                                    @class(['is-invalid' => $errors->has('services')])>{{ old('services') }}</textarea>
                                @error('services')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="ar_services">{{ __('messages.Services in Arabic') }} <b>*</b></label>
                                <textarea name="ar_services" id="ar_services" rows="3" dir="rtl" lang="ar" required maxlength="5000"
                                    placeholder="طاقة شمسية للمنازل، أنظمة هجينة، مضخات مياه"
                                    @class(['is-invalid' => $errors->has('ar_services')])>{{ old('ar_services') }}</textarea>
                                @error('ar_services')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </section>

                    {{-- 3. Company profile (Quill editors, see public/main/js/script.js) --}}
                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">3</span>
                            <div>
                                <h2>{{ __('messages.Company profiles') }}</h2>
                                <p>{{ __('messages.Describe your experience, solutions, and service areas in both languages.') }}</p>
                            </div>
                        </div>

                        <div class="provider-profile-grid">
                            <div class="provider-field">
                                <label for="companyEditor">{{ __('messages.Company profile in English') }} <b>*</b></label>
                                <div id="companyEditor" class="provider-quill-editor" ></div>
                                <input type="hidden" name="description" id="description" value="{{ old('description') }}">
                            </div>

                            <div class="provider-field">
                                <label for="companyEditorAr">{{ __('messages.Company profile in Arabic') }} <b>*</b></label>
                                <div id="companyEditorAr" class="provider-quill-editor" dir="rtl"></div>
                                <input type="hidden" name="ar_description" id="ar_description" value="{{ old('ar_description') }}">
                            </div>
                        </div>
                    </section>

                    {{-- 4. Login details --}}
                    <section class="provider-form-section">
                        <div class="provider-section-heading">
                            <span class="provider-section-number">4</span>
                            <div>
                                <h2>{{ __('messages.Login details') }}</h2>
                                <p>{{ __('messages.You will use these to sign in to your company dashboard.') }}</p>
                            </div>
                        </div>

                        <div class="provider-form-grid">
                            <div class="provider-field">
                                <label for="username">{{ __('messages.Username') }} <b>*</b></label>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="{{ __('messages.username') }}"
                                    maxlength="191" required autocomplete="username" data-latin-only
                                    data-latin-message="{{ __('messages.Please use English letters only for your username.') }}"
                                    @class(['is-invalid' => $errors->has('username')])>
                                <small>{{ __('messages.English letters, numbers and _ (e.g. tesla_solar).') }}</small>
                                <small class="provider-field-error" data-latin-error hidden></small>
                                @error('username')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="provider-field">
                                <label for="email">{{ __('messages.Business email') }} <b>*</b></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="100"
                                    required autocomplete="email" placeholder="company@example.com"
                                    @class(['is-invalid' => $errors->has('email')])>
                                <small>{{ __('messages.We send approval news and password resets here.') }}</small>
                                @error('email')
                                    <small class="provider-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Must match the "min:8" password rule in WebsiteController::storeCompany --}}
                            @include('visitors.partials.password-fields', ['minLength' => 8])
                        </div>
                    </section>

                    <div class="provider-register-footer">
                        <p>
                            {{ __('messages.By creating an account, you agree that your company information will be reviewed before publication.') }}
                            <a href="{{ route('terms', ['lang' => $locale]) }}" target="_blank">{{ __('messages.Terms') }}</a>
                            ·
                            <a href="{{ route('privacy-policy', ['lang' => $locale]) }}" target="_blank">{{ __('messages.Privacy') }}</a>
                        </p>

                        <button type="submit" class="provider-submit-button">{{ __('messages.Create company account') }}</button>

                        <p class="provider-login-link">
                            {{ __('messages.Already have an account?') }}
                            <a href="{{ route('company.login') }}">{{ __('messages.Sign in') }}</a>
                        </p>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset_v('main/js/script.js') }}"></script>
    <script src="{{ asset_v('visitors/js/register.js') }}" defer></script>
@endsection
