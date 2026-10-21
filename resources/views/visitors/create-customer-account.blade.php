@extends('visitors.layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'SunXFinder - أنشئ حسابك' : 'SunXFinder - Create Account')
@section('content')
    @php
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

    <div class="provider-register-page">
        <div class="provider-register-container">
            <header class="provider-register-heading">
                <a href="{{ route('home', ['lang' => app()->getLocale()]) }}" class="provider-back-link"
                    style="direction:ltr">
                    ← {{ trans('messages.Back to home') }}
                </a>

                <div class="provider-heading-icon" aria-hidden="true">☀</div>
                <h1>{{ trans('messages.Create your customer account') }}</h1>
                <p>{{ trans('messages.Join SunXFinder to connect with solar providers.') }}</p>
            </header>

            @if (session('success'))
                <div class="provider-alert provider-alert-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="provider-alert provider-alert-error" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="provider-alert provider-alert-error" role="alert">
                    <strong>{{ trans('messages.Please correct the following errors:') }}</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('customer.register.store') }}" method="POST" class="provider-register-form">
                @csrf

                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-number">01</span>
                        <div>
                            <h2>{{ trans('messages.Personal information') }}</h2>
                            <p>{{ trans('messages.Enter your name and contact details.') }}</p>
                        </div>
                    </div>

                    <div class="provider-form-grid">
                        <div class="provider-field provider-field-full">
                            <label for="username">{{ trans('messages.Username') }} <b>*</b></label>
                            <input type="text" id="username" name="username" value="{{ old('username') }}" maxlength="191"
                                autocomplete="username" required placeholder="{{ trans('messages.Choose a username') }}">
                            @error('username')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field">
                            <label for="first_name">{{ trans('messages.First name') }} <b>*</b></label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                placeholder="{{ trans('messages.Enter first name') }}" maxlength="80"
                                autocomplete="given-name" style="text-transform: capitalize;" required>
                            @error('first_name')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field">
                            <label for="second_name">{{ trans('messages.Second name') }} <b>*</b></label>
                            <input type="text" id="second_name" name="second_name" value="{{ old('second_name') }}"
                                placeholder="{{ trans('messages.Enter second name') }}" maxlength="80"
                                style="text-transform: capitalize;" required>
                            @error('second_name')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field provider-field-full">
                            <label for="third_name">{{ trans('messages.Third name') }} <b>*</b></label>
                            <input type="text" id="third_name" name="third_name" value="{{ old('third_name') }}"
                                placeholder="{{ trans('messages.Enter third name') }}" maxlength="80"
                                autocomplete="family-name" style="text-transform: capitalize;" required>
                            @error('third_name')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror

                        </div>

                        <div class="provider-field">
                            <label for="phone">{{ trans('messages.Phone number') }} <b>*</b></label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30"
                                autocomplete="tel" inputmode="tel" required placeholder="09...">
                            @error('phone')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field">
                            <label for="email">{{ trans('messages.Email address') }} <b>*</b></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="191"
                                autocomplete="email" required placeholder="you@example.com">
                            @error('email')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field provider-field-full">
                            <label for="gender">{{ trans('messages.Gender') }} <b>*</b></label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled {{ old('gender') ? '' : 'selected' }}>
                                    {{ trans('messages.Select gender') }}
                                </option>
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>
                                    {{ trans('messages.Male') }}
                                </option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>
                                    {{ trans('messages.Female') }}
                                </option>
                            </select>
                            @error('gender')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-number">02</span>
                        <div>
                            <h2>{{ trans('messages.Address information') }}</h2>
                            <p>{{ trans('messages.Select your city and enter your area and street details.') }}</p>
                        </div>
                    </div>

                    <div class="provider-form-grid">
                        <div class="provider-field provider-field-full">
                            <label for="city">{{ trans('messages.City') }} <b>*</b></label>
                            <select id="city" name="city" required>
                                <option value="" disabled {{ old('city') ? '' : 'selected' }}>
                                    {{ trans('messages.Select your city') }}
                                </option>

                                @foreach ($cities as $english => $arabic)
                                    <option value="{{ $english }}" {{ old('city') === $english ? 'selected' : '' }}>
                                        {{ app()->getLocale() === 'ar' ? $arabic : $english }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field provider-field-full">
                            <label for="address_details">{{ trans('messages.District, neighborhood and street') }}
                                <b>*</b></label>
                            <textarea id="address_details" name="address_details" rows="3" maxlength="2000" required
                                placeholder="{{ trans('messages.Example: Bahri, Kobar, Square 5, Street 12') }}">{{ old('address_details') }}</textarea>
                            @error('address_details')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror

                        </div>
                    </div>
                </section>

                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-number">03</span>
                        <div>
                            <h2>{{ trans('messages.Account security') }}</h2>
                            <p>{{ trans('messages.Create a strong password for your account.') }}</p>
                        </div>
                    </div>

                    <div class="provider-form-grid">
                        <div class="provider-field">
                            <label for="password">{{ trans('messages.Password') }} <b>*</b></label>
                            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
                                required placeholder="{{ trans('messages.At least 5 characters') }}">
                            @error('password')
                                <small class="provider-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="provider-field">
                            <label for="password_confirmation">{{ trans('messages.Confirm password') }} <b>*</b></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                                autocomplete="new-password" required
                                placeholder="{{ trans('messages.Re-enter your password') }}">
                        </div>
                    </div>
                </section>

                <footer class="provider-register-footer">
                    <p>{{ trans('messages.By creating an account, you agree to provide accurate contact information.') }}
                    </p>
                    <button type="submit" class="provider-submit-button">
                        {{ trans('messages.Create customer account') }}
                    </button>
                    {{-- <p class="provider-login-link">
                        {{ trans('messages.Already have an account?') }}
                        <a href="{{ route('customer.login') }}">{{ trans('messages.Sign in') }}</a>
                    </p> --}}
                </footer>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usernameInput = document.getElementById('username');

            if (!usernameInput) return;

            const url = window.location.href.toLowerCase();

            const message = url.includes('lang=ar') || url.includes('/ar')
                ? 'يرجى استخدام الأحرف الإنجليزية فقط لاسم المستخدم.'
                : 'Please use English letters only for your username.';

            const arabicPattern = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/;

            usernameInput.addEventListener('input', function () {
                if (arabicPattern.test(this.value)) {
                    alert(message);

                    this.value = this.value.replace(
                        /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/g,
                        ''
                    );
                }
            });
        });

        document.querySelectorAll(
            '#first_name, #second_name, #third_name'
        ).forEach(function (input) {
            input.addEventListener('input', function () {
                const start = this.selectionStart;
                const end = this.selectionEnd;

                this.value = this.value.replace(/\b[a-z]/g, function (letter) {
                    return letter.toUpperCase();
                });

                this.setSelectionRange(start, end);
            });
        });
    </script>


@endsection


<style>
    /* Customer form uses the same shared SunXFinder styles as company registration. */
    /* .provider-register-container,
    .provider-register-form,
    .provider-form-section,
    .provider-form-grid,
    .provider-field,
    .provider-field-full {
        min-width: 0;
        max-width: 100%;
    }

    @media (max-width: 600px) {
        .provider-register-page {
            padding-left: 0;
            padding-right: 0;
        }

        .provider-register-container {
            width: calc(100% - 20px);
            margin-inline: auto;
            padding: 20px 14px;
        }

        .provider-form-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .provider-field input,
        .provider-field select,
        .provider-field textarea {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
    } */
</style>