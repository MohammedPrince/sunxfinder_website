@extends('company.layouts.app')

@section('title', __('company/messages.navigation.company_profile'))
@section('breadcrumb', __('company/messages.navigation.company_profile'))
@section('eyebrow', __('company/messages.profile.eyebrow'))
@section('page-title', __('company/messages.navigation.company_profile'))
@section('page-description', __('company/messages.profile.page_description'))

@section('content')
    @php
        $companyLogo = $company->logo_path ?? null;

        if ($companyLogo && !\Illuminate\Support\Str::startsWith($companyLogo, ['http://', 'https://'])) {
            $companyLogo = asset($companyLogo);
        }

        $plainDescription = html_entity_decode(
            strip_tags($company->description ?? ''),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $plainArabicDescription = html_entity_decode(
            strip_tags($company->ar_description ?? ''),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
    @endphp

    <div class="company-profile-admin-style">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>{{ __('company/messages.common.fix_errors') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('company.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Company identity --}}
            <section class="panel">
                <div class="panel-heading company-profile-heading">
                    <div class="company-profile-identity">
                        <div class="company-profile-logo-wrapper">
                            @if ($companyLogo)
                                <img id="companyLogoPreview" src="{{ $companyLogo }}" alt="{{ __('company/messages.profile.logo_alt', ['name' => $company->name]) }}"
                                    class="company-profile-logo">
                                <div id="companyLogoPlaceholder" class="company-profile-logo-placeholder" hidden>
                                    {{ name_initials($company->name ?? 'S') }}
                                </div>
                            @else
                                <div id="companyLogoPlaceholder" class="company-profile-logo-placeholder">
                                    {{ name_initials($company->name ?? 'S') }}
                                </div>
                                <img id="companyLogoPreview" src="" alt="{{ __('company/messages.profile.logo_preview') }}" class="company-profile-logo"
                                    hidden>
                            @endif
                        </div>

                        <div class="company-profile-identity-copy">
                            <span class="company-profile-eyebrow">{{ __('company/messages.profile.info_eyebrow') }}</span>
                            <h2>{{ $company->name }}</h2>
                            <p>{{ $company->city ?: __('company/messages.profile.city_not_provided') }}</p>
                            <div class="company-profile-badges">
                                <span class="company-profile-badge">{{ company_label('common.' . ($company->status ?? 'pending'), ucfirst($company->status ?? 'pending')) }}</span>
                                @if ($company->is_verified)
                                    <span class="company-profile-badge is-verified">{{ __('company/messages.profile.verified') }}</span>
                                @else
                                    <span class="company-profile-badge is-pending">{{ __('company/messages.profile.verification_pending') }}</span>
                                @endif
                            </div>

                            <div class="company-status-message">
                                <p></p>
                                @if (!$company->is_verified || empty($company->license_number) || empty($company->phone) || empty($company->email))
                                    <p>
                                        <span class="pill pill-yellow">
                                            {{ __('company/messages.profile.incomplete') }}
                                        </span>
                                    </p>
                                @elseif (
                                        $company->is_verified &&
                                        $company->status === 'pending' &&
                                        !empty($company->license_number) &&
                                        !empty($company->phone) &&
                                        !empty($company->email)
                                    )
                                    <p>
                                        <span class="pill pill-yellow">
                                            {{ __('company/messages.profile.waiting_approval') }}
                                        </span>
                                    </p>
                                @elseif ($company->is_verified && $company->status === 'approved')
                                    <p>
                                        <span class="pill pill-green">
                                            {{ __('company/messages.profile.approved_message') }}
                                        </span>
                                    </p>
                                @elseif ($company->status === 'rejected')
                                    <p>
                                        <span class="pill pill-red">
                                            {{ __('company/messages.profile.rejected_message') }}
                                        </span>
                                    </p>
                                @endif
                            </div>

                        </div>
                    </div>

                </div>
            </section>

            {{-- Company information --}}
            <section class="panel">
                <div class="panel-heading">
                    <h2>{{ __('company/messages.profile.section_info') }}</h2>
                    <p>{{ __('company/messages.profile.section_info_sub') }}</p>
                </div>

                <div class="panel-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">{{ __('company/messages.profile.name') }} <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="name" name="name" minlength="10" maxlength="200"
                                dir="auto" placeholder="{{ __('company/messages.profile.name_placeholder') }}"
                                value="{{ old('name', $company->name) }}" required>
                            @error('name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="username">{{ __('company/messages.profile.username') }} <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="username" value="{{ $company->username }}"
                                placeholder="{{ __('company/messages.profile.username_placeholder') }}" readonly>
                            <small class="form-hint">{{ __('company/messages.profile.username_hint') }}</small>
                        </div>

                        <div class="form-group">
                            <label for="email">{{ __('company/messages.profile.email') }} <b class="required-mark">*</b></label>
                            <input class="form-control" type="email" id="email" name="email" maxlength="191"
                                placeholder="{{ __('company/messages.profile.email_placeholder') }}"
                                value="{{ old('email', $company->email) }}" required>
                            @error('email')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">{{ __('company/messages.profile.phone') }} <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="phone" name="phone" maxlength="30"
                                placeholder="{{ __('company/messages.profile.phone_placeholder') }}"
                                value="{{ old('phone', $company->phone) }}" required>
                            @error('phone')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="year_of_exp">{{ __('company/messages.profile.years') }} <b style="color: red">*</b></label>
                            <input class="form-control" type="number" name="year_of_exp" required
                                value="{{ old('year_of_exp', $company->year_of_exp ?? '') }}"
                                placeholder="{{ __('company/messages.profile.years_placeholder') }}">
                            @error('year_of_exp')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="number_of_projects">{{ __('company/messages.profile.projects_count') }} <b style="color: red">*</b></label>
                            <input class="form-control" type="number" name="number_of_projects" required
                                value="{{ old('number_of_projects', $company->number_of_projects ?? '') }}"
                                placeholder="{{ __('company/messages.profile.projects_placeholder') }}">
                            @error('number_of_projects')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>


                        {{-- Change Password --}}
                        <div class="form-group">
                            <label for="password">{{ __('company/messages.profile.new_password') }}</label>

                            <input class="form-control" type="password" id="password" name="password" minlength="8"
                                autocomplete="new-password" placeholder="{{ __('company/messages.profile.new_password_placeholder') }}">

                            <small class="form-hint">
                                {{ __('company/messages.profile.password_hint') }}
                            </small>

                            @error('password')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-group">
                            <label for="password_confirmation">{{ __('company/messages.profile.confirm_password') }}</label>

                            <input class="form-control" type="password" id="password_confirmation"
                                name="password_confirmation" minlength="8" autocomplete="new-password"
                                placeholder="{{ __('company/messages.profile.confirm_placeholder') }}">

                            @error('password_confirmation')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label for="city">{{ __('company/messages.common.city') }} <b class="required-mark">*</b></label>
                            <select class="select-control" id="city" name="city" required>
                                <option value="">{{ __('company/messages.profile.select_city') }}</option>
                                @foreach ([__('company/messages.common.city_khartoum'), __('company/messages.common.city_port_sudan'), __('company/messages.common.city_atbara'), __('company/messages.common.city_wad_madani'), __('company/messages.common.city_merowe'), __('company/messages.common.city_kassala'), __('company/messages.common.city_al_qadarif'), __('company/messages.common.city_dongola'), __('company/messages.common.city_halfa')] as $city)
                                    <option value="{{ $city }}" @selected(old('city', $company->city) === $city)>{{ __('company/messages.common.city_' . \Illuminate\Support\Str::snake(strtolower($city))) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="license_number">{{ __('company/messages.profile.license') }} <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="license_number" name="license_number"
                                placeholder="{{ __('company/messages.profile.license_placeholder') }}" maxlength="191"
                                value="{{ old('license_number', $company->license_number) }}" required>
                            <small class="form-hint">{{ __('company/messages.profile.license_hint') }}</small>
                            @error('license_number')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="logo">
                                {{ __('company/messages.profile.logo') }}
                                @if (empty($company->logo_path))
                                    <b class="required-mark">*</b>
                                @endif
                            </label>

                            <input class="form-control" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp" @if (empty($company->logo_path)) required @endif>
                            <small class="form-hint">
                                {{ __('company/messages.profile.logo_hint') }}
                                @if (!empty($company->logo_path))
                                    {{ __('company/messages.profile.logo_keep') }}
                                @endif
                            </small>

                            @error('logo')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label for="social_media">{{ __('company/messages.profile.social') }}</label>
                            <textarea class="form-control" id="social_media" name="social_media" rows="3" maxlength="2000"
                                placeholder="{{ __('company/messages.profile.social_placeholder') }}">{{ old('social_media', $company->social_media) }}</textarea>
                            <small class="form-hint">{{ __('company/messages.profile.social_hint') }}</small>
                            @error('social_media')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- Company profile and services --}}
            <section class="panel">
                <div class="panel-heading">
                    <h2>{{ __('company/messages.profile.section_services') }}</h2>
                    <p>{{ __('company/messages.profile.section_services_sub') }}</p>
                </div>

                <div class="panel-body">
                    <div class="form-grid company-profile-grid">
                        {{-- English Company Profile --}}
                        <div class="form-group">
                            <label for="companyEditor">
                                {{ __('company/messages.profile.description') }} <b class="required-mark">*</b>
                            </label>

                            <div class="editor-hint">
                                {{ __('company/messages.profile.description_hint') }}
                            </div>

                            <div id="companyEditor">{!! old('description', $sanitizedDescription ?? '') !!}</div>

                            <input type="hidden" name="description" id="description"
                                placeholder="{{ __('company/messages.profile.description_placeholder') }}"
                                value="{{ old('description', $company->description ?? '') }}" required>

                            @error('description')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Arabic Company Profile --}}
                        <div class="form-group">
                            <label for="companyEditorAr">
                                {{ __('company/messages.profile.description_ar') }} <b class="required-mark">*</b>
                            </label>

                            <div class="editor-hint">
                                .اكتب نبذة عن الشركة وخدمات الطاقة الشمسية والخبرات والمناطق التي تغطيها
                            </div>

                            <div id="companyEditorAr">{!! $sanitizedArDescription ?? '' !!}</div>

                            <input type="hidden" name="ar_description" id="ar_description"
                                placeholder="{{ __('company/messages.profile.description_ar_placeholder') }}"
                                value="{{ old('ar_description', $company->ar_description ?? '') }}" required>

                            @error('ar_description')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        @include('admin.partials.services-editor', [
                            'services' => $company->services ?? '',
                            'arServices' => $company->ar_services ?? '',
                        ])
                    </div>
                </div>
            </section>

            {{-- Account status is read-only for the company account --}}
            <section class="panel">
                <div class="panel-heading">
                    <h2>{{ __('company/messages.profile.account_status') }}</h2>
                    <p>{{ __('company/messages.profile.account_status_sub') }}</p>
                </div>

                <div class="panel-body">
                    <div class="form-grid">

                        {{-- Approval Status --}}
                        <div class="form-group">
                            <label>{{ __('company/messages.profile.approval_status') }}</label>
                            @php
                                $status = strtolower($company->status ?? 'pending');

                                $statusClass = match ($status) {
                                    'approved' => 'pill-green',
                                    'pending' => 'pill-yellow',
                                    'rejected' => 'pill-red',
                                    default => 'pill-gray',
                                };
                            @endphp

                            <div>
                                <span class="pill {{ $statusClass }}">
                                    {{ company_label('common.' . $status, ucfirst($status)) }}
                                </span>
                            </div>
                        </div>

                        {{-- Company Verification --}}
                        <div class="form-group">
                            <label>{{ __('company/messages.profile.company_verified') }}</label>

                            <div>
                                @if ($company->is_verified)
                                    <span class="pill pill-green">{{ __('company/messages.profile.verified') }}</span>
                                @else
                                    <span class="pill pill-yellow">{{ __('company/messages.profile.not_verified') }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Account Active --}}
                        <div class="form-group">
                            <label>{{ __('company/messages.profile.account_active') }}</label>

                            <div>
                                @if ($company->is_active)
                                    <span class="pill pill-green">{{ __('company/messages.common.active') }}</span>
                                @else
                                    <span class="pill pill-red">{{ __('company/messages.common.inactive') }}</span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

            </section>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">{{ __('company/messages.common.save_changes') }}</button>
                @if ($company->is_verified && $company->status != 'pending')
                    <a href="{{ url('/companies/' . $company->slug) }}?lang={{ request('lang', app()->getLocale()) }}"
                        class="btn btn-light" target="_blank" rel="noopener noreferrer">
                        {{ __('company/messages.profile.view_on_website') }}
                    </a>
                @endif

            </div>
        </form>
    </div>

    @push('scripts')
        <script src="{{ asset('main/js/script.js') }}"></script>
        <script src="{{ asset_v('main/js/services-editor.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const logoInput = document.getElementById('logo');
                const logoPreview = document.getElementById('companyLogoPreview');
                const logoPlaceholder = document.getElementById('companyLogoPlaceholder');

                if (!logoInput || !logoPreview) return;

                logoInput.addEventListener('change', function (event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file) return;

                    if (!file.type.startsWith('image/')) {
                        logoInput.value = '';
                        alert(@json(__('company/messages.profile.invalid_image')));
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        logoPreview.src = e.target.result;
                        logoPreview.hidden = false;
                        if (logoPlaceholder) logoPlaceholder.hidden = true;
                    };
                    reader.readAsDataURL(file);
                });
            });
        </script>
    @endpush
@endsection
