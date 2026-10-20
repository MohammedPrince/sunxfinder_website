@extends('visitors.layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'SunXFinder - أنشئ حساب لشركتك' : 'SunXFinder - Create Company Account')
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

            <div class="provider-register-heading">

                <a href="{{ route('home', ['lang' => app()->getLocale()]) }}" class="provider-back-link"
                    style="direction: ltr;">

                    ← {{ trans('messages.Back to home') }}

                </a>

                <div class="provider-heading-icon">☀</div>

                {{-- <h1 class="brand-fallback">Sun<span>X</span>Finder</h1> --}}
                <h1>{{ trans('messages.Create your company account') }}</h1>

                <p>{{ trans('messages.Create your solar company account and connect with customers.') }}</p>

            </div>


            @if (session('success'))
                <div class="provider-alert provider-alert-success">

                    {{ session('success') }}

                </div>
            @endif


            @if (session('error'))
                <div class="provider-alert provider-alert-error">

                    {{ session('error') }}

                </div>
            @endif

            @if ($errors->any())
                <div class="provider-alert provider-alert-error">

                    <strong>{{ trans('messages.Please correct the following errors:') }}</strong>

                    <ul>

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>
            @endif

            <form action="{{ route('company.register.store') }}" method="POST" class="provider-register-form"
                enctype="multipart/form-data">

                @csrf

                <section class="provider-form-section">

                    <div class="provider-section-heading">

                        <span class="provider-section-number">01</span>

                        <div>

                            <h2>{{ trans('messages.Company information') }}</h2>

                            <p>{{ trans('messages.Tell customers about your solar business.') }}</p>

                        </div>

                    </div>

                    <div class="provider-form-grid">

                        <div class="provider-field provider-field-full">

                            <label for="name">

                                {{ trans('messages.Company name') }} <b>*</b>

                            </label>

                            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="200"
                                required autocomplete="organization"
                                placeholder="{{ trans('messages.Enter your registered company name') }}">

                        </div>

                        {{-- Company Logo --}}

                        {{-- <div class="provider-field provider-field-full">

                            <label for="logo_path">{{ trans('messages.Company Logo') }}</label>

                            <input type="file" name="logo_path" id="logo_path" accept="image/png,image/jpeg,image/webp">

                            <small>

                                {{ trans('messages.Upload your company logo (PNG, JPG, or WEBP; maximum 2 MB).') }}

                            </small>

                            @error('logo_path')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            <div id="logoPreviewContainer" style="display:none; margin-top:12px;">

                                <img id="logoPreview" src="" alt="{{ trans('messages.Company logo preview') }}"
                                    style="max-width:140px; max-height:140px; object-fit:contain; border-radius:8px;">

                            </div>

                        </div> --}}

                        <div class="provider-field">

                            <label for="username">

                                {{ trans('messages.Username') }} <b>*</b>

                            </label>

                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                maxlength="191" required autocomplete="username"
                                placeholder="{{ trans('messages.Choose a username') }}">

                        </div>

                        <div class="provider-field">

                            <label for="city">

                                {{ trans('messages.City') }} <b>*</b>

                            </label>

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
                                <small class="field-error">{{ $message }}</small>
                            @enderror

                        </div>

                        <div class="provider-field">

                            <label for="email">{{ trans('messages.Business email') }} <b>*</b></label>

                            <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="191"
                                required autocomplete="email" placeholder="company@example.com">

                        </div>

                        <div class="provider-field">

                            <label for="phone">{{ trans('messages.Phone number') }} <b>*</b></label>

                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30"
                                required autocomplete="tel" placeholder="+249...">

                        </div>

                        {{-- Year of Experience && Number of Projects --}}
                        <div class="provider-field">
                            <label for="year_of_exp">{{ trans('messages.Year of Experience') }} <b>*</b></label>
                            <input type="number" id="year_of_exp" name="year_of_exp" value="{{ old('year_of_exp') }}"  required placeholder="{{ trans('messages.Year of Experience') }}">
                        </div>

                           <div class="provider-field">
                            <label for="number_of_projects">{{ trans('messages.Number of Projects') }} <b>*</b></label>
                            <input type="number" id="number_of_projects" name="number_of_projects" value="{{ old('number_of_projects') }}"  required placeholder="{{ trans('messages.Number of Projects') }}">
                        </div>


                        {{-- Social Media --}}

                        <div class="provider-field provider-field-full">

                            <label for="social_media">{{ trans('messages.Social Media Links') }}</label>


                            <textarea name="social_media" id="social_media" rows="3"
                                placeholder="{{ trans('messages.Enter your social media links, one per line') }}">{{ old('social_media') }}</textarea>


                            @error('social_media')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror


                            <small>{{ trans('messages.Example: Facebook, Instagram, LinkedIn, or website URLs.') }}</small>

                        </div>

                    </div>

                </section>


                <section class="provider-form-section">

                    <div class="provider-section-heading">

                        <span class="provider-section-number">02</span>

                        <div>

                            <h2>{{ trans('messages.Solar services') }}</h2>

                            <p>{{ trans('messages.List the services your company provides.') }}</p>

                        </div>

                    </div>


                    <div class="provider-form-grid">

                        <div class="provider-field">

                            <label for="services">

                                {{ trans('messages.Services in English') }} <b>*</b>

                            </label>

                            <textarea name="services" id="services" rows="3" required
                                placeholder="Commercial solar, Hybrid systems, Off-grid systems">{{ old('services') }}</textarea>

                            <small>{{ trans('messages.Separate services with commas.') }}</small>

                        </div>


                        <div class="provider-field">

                            <label for="ar_services">

                                {{ trans('messages.Services in Arabic') }} <b>*</b>

                            </label>

                            <textarea name="ar_services" id="ar_services" rows="3" dir="rtl" lang="ar" required
                                placeholder="الطاقة الشمسية التجارية، الأنظمة الهجينة، الأنظمة خارج الشبكة">{{ old('ar_services') }}</textarea>

                            <small>{{ trans('messages.Separate services with commas.') }}</small>

                        </div>

                    </div>

                </section>


                <section class="provider-form-section">

                    <div class="provider-section-heading">

                        <span class="provider-section-number">03</span>

                        <div>

                            <h2>{{ trans('messages.Company profiles') }}</h2>

                            <p>{{ trans('messages.Describe your experience, solutions, and service areas in both languages.') }}

                            </p>

                        </div>

                    </div>


                    <div class="provider-profile-grid">

                        <div class="provider-field">

                            <label for="companyEditor">

                                {{ trans('messages.Company profile in English') }} <b>*</b>

                            </label>

                            <div id="companyEditor" class="provider-quill-editor"
                                data-initial-content="{{ old('description') }}"></div>

                            <input type="hidden" name="description" id="description" value="{{ old('description') }}"
                                required>

                        </div>


                        <div class="provider-field">

                            <label for="companyEditorAr">

                                {{ trans('messages.Company profile in Arabic') }} <b>*</b>

                            </label>

                            <div id="companyEditorAr" class="provider-quill-editor" dir="rtl"
                                data-initial-content="{{ old('ar_description') }}"></div>

                            <input type="hidden" name="ar_description" id="ar_description"
                                value="{{ old('ar_description') }}" required>

                        </div>

                    </div>

                </section>


                <section class="provider-form-section" style="padding-top:100px;">

                    <div class="provider-section-heading">

                        <span class="provider-section-number">04</span>

                        <div>

                            <h2>{{ trans('messages.Account security') }}</h2>

                            <p>{{ trans('messages.Create a strong password for your company account.') }}</p>

                        </div>

                    </div>


                    <div class="provider-form-grid">

                        <div class="provider-field">

                            <label for="password">{{ trans('messages.Password') }} <b>*</b></label>

                            <input type="password" id="password" name="password" required minlength="8"
                                autocomplete="new-password" placeholder="{{ trans('messages.At least 8 characters') }}">

                        </div>

                        <div class="provider-field">

                            <label for="password_confirmation">

                                {{ trans('messages.Confirm password') }} <b>*</b>

                            </label>

                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                minlength="8" autocomplete="new-password"
                                placeholder="{{ trans('messages.Re-enter your password') }}">

                        </div>

                    </div>

                </section>


                <div class="provider-register-footer">

                    <p>

                        {{ trans('messages.By creating an account, you agree that your company information will be reviewed before publication.') }}

                    </p>


                    <button type="submit" class="provider-submit-button">

                        {{ trans('messages.Create company account') }}


                    </button>


                    <p class="provider-login-link">

                        {{ trans('messages.Already have an account?') }}

                        <a href="{{ route('company.login') }}">

                            {{ trans('messages.Sign in') }}

                        </a>

                    </p>

                </div>

            </form>

        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoInput = document.getElementById('logo_path');
            const logoPreview = document.getElementById('logoPreview');
            const logoPreviewContainer = document.getElementById('logoPreviewContainer');
            let previewUrl = null;

            if (logoInput && logoPreview && logoPreviewContainer) {
                logoInput.addEventListener('change', function() {
                    const file = this.files && this.files[0];
                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                        previewUrl = null;
                    }
                    if (!file) {
                        logoPreview.removeAttribute('src');
                        logoPreviewContainer.style.display = 'none';
                        return;
                    }
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
                    if (!allowedTypes.includes(file.type) || file.size > 2 * 1024 * 1024) {
                        alert('Please select a PNG, JPG, or WEBP image no larger than 2 MB.');
                        this.value = '';
                        logoPreview.removeAttribute('src');
                        logoPreviewContainer.style.display = 'none';
                        return;
                    }
                    previewUrl = URL.createObjectURL(file);
                    logoPreview.src = previewUrl;
                    logoPreviewContainer.style.display = 'block';
                });
            }


        });
    </script>

@endsection

@section('scripts')
    <script src="{{ asset('main/js/script.js') }}"></script>
@endsection
