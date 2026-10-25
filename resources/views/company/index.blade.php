@extends('company.layouts.app')

@section('title', 'Company Profile')
@section('breadcrumb', 'Company Profile')
@section('eyebrow', 'COMPANY ACCOUNT')
@section('page-title', 'Company Profile')
@section('page-description', 'Manage the information customers see on your public company profile.')

@section('content')
    @php
        $companyLogo = $company->logo_path ?? null;

        if ($companyLogo && !\Illuminate\Support\Str::startsWith($companyLogo, ['http://', 'https://'])) {
            $companyLogo = asset($companyLogo);
        }

        $servicesValue = is_array($company->services) ? implode(', ', $company->services) : $company->services ?? '';

        $arabicServicesValue = is_array($company->ar_services)
            ? implode('، ', $company->ar_services)
            : $company->ar_services ?? '';

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
                <strong>Please correct the following errors:</strong>
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
                                <img id="companyLogoPreview" src="{{ $companyLogo }}" alt="{{ $company->name }} logo"
                                    class="company-profile-logo">
                                <div id="companyLogoPlaceholder" class="company-profile-logo-placeholder" hidden>
                                    {{ strtoupper(mb_substr($company->name ?? 'S', 0, 1)) }}
                                </div>
                            @else
                                <div id="companyLogoPlaceholder" class="company-profile-logo-placeholder">
                                    {{ strtoupper(mb_substr($company->name ?? 'S', 0, 1)) }}
                                </div>
                                <img id="companyLogoPreview" src="" alt="Company logo preview" class="company-profile-logo"
                                    hidden>
                            @endif
                        </div>

                        <div class="company-profile-identity-copy">
                            <span class="company-profile-eyebrow">COMPANY INFORMATION</span>
                            <h2>{{ $company->name }}</h2>
                            <p>{{ $company->city ?: 'City not provided' }}</p>
                            <div class="company-profile-badges">
                                <span class="company-profile-badge">{{ ucfirst($company->status ?? 'pending') }}</span>
                                @if ($company->is_verified)
                                    <span class="company-profile-badge is-verified">Verified</span>
                                @else
                                    <span class="company-profile-badge is-pending">Verification pending</span>
                                @endif
                            </div>

                            <div class="company-status-message">
                                <p></p>
                                @if (!$company->is_verified || empty($company->license_number) || empty($company->phone) || empty($company->email))
                                    <p>
                                        <span class="pill pill-yellow">
                                            Your company profile is incomplete. Please provide the required
                                            information, including your license number, phone number, and
                                            email address, to complete verification.
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
                                            Your company information is complete and is waiting for
                                            SunXFinder approval. Please wait. Meanwhile, you can still
                                            update your information. Thank you.
                                        </span>
                                    </p>
                                @elseif ($company->is_verified && $company->status === 'approved')
                                    <p>
                                        <span class="pill pill-green">
                                            Your company has been verified and approved by SunXFinder.
                                        </span>
                                    </p>
                                @elseif ($company->status === 'rejected')
                                    <p>
                                        <span class="pill pill-red">
                                            Your company application has been rejected. Please contact
                                            SunXFinder support for further information.
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
                    <h2>Company Information</h2>
                    <p>Manage the public profile, license, and contact details customers see.</p>
                </div>

                <div class="panel-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Company Name <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="name" name="name" minlength="10" maxlength="200"
                                dir="auto" placeholder="Enter your company's official name (English or Arabic)"
                                value="{{ old('name', $company->name) }}" required>
                            @error('name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="username">Company Username <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="username" value="{{ $company->username }}"
                                placeholder="Enter your company's username" readonly>
                            <small class="form-hint">Contact support if you need to change your username.</small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <b class="required-mark">*</b></label>
                            <input class="form-control" type="email" id="email" name="email" maxlength="191"
                                placeholder="Enter your company's official email"
                                value="{{ old('email', $company->email) }}" required>
                            @error('email')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="phone" name="phone" maxlength="30"
                                placeholder="Enter your company's official phone"
                                value="{{ old('phone', $company->phone) }}" required>
                            @error('phone')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="year_of_exp">Years Experience <b style="color: red">*</b></label>
                            <input class="form-control" type="number" name="year_of_exp" required
                                value="{{ old('year_of_exp', $company->year_of_exp ?? '') }}"
                                placeholder="How many years you are in the market?">
                            @error('year_of_exp')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="number_of_projects">Number of Projects <b style="color: red">*</b></label>
                            <input class="form-control" type="number" name="number_of_projects" required
                                value="{{ old('number_of_projects', $company->number_of_projects ?? '') }}"
                                placeholder="How many project you complete?">
                            @error('number_of_projects')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>


                        {{-- Change Password --}}
                        <div class="form-group">
                            <label for="password">New Password</label>

                            <input class="form-control" type="password" id="password" name="password" minlength="8"
                                autocomplete="new-password" placeholder="Leave blank to keep your current password">

                            <small class="form-hint">
                                Minimum 8 characters, including uppercase, lowercase,
                                a number, and a special character.
                            </small>

                            @error('password')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>

                            <input class="form-control" type="password" id="password_confirmation"
                                name="password_confirmation" minlength="8" autocomplete="new-password"
                                placeholder="Confirm your new password">

                            @error('password_confirmation')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label for="city">City <b class="required-mark">*</b></label>
                            <select class="select-control" id="city" name="city" required>
                                <option value="">Select city</option>
                                @foreach (['Khartoum', 'Port Sudan', 'Atbara', 'Wad Madani', 'Merowe', 'Kassala', 'Al Qadarif', 'Dongola', 'Halfa'] as $city)
                                    <option value="{{ $city }}" @selected(old('city', $company->city) === $city)>{{ $city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="license_number">License Number <b class="required-mark">*</b></label>
                            <input class="form-control" type="text" id="license_number" name="license_number"
                                placeholder="Enter your company's official license number" maxlength="191"
                                value="{{ old('license_number', $company->license_number) }}" required>
                            <small class="form-hint">Enter your company's official license number.</small>
                            @error('license_number')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="logo">
                                Company Logo
                                @if (empty($company->logo_path))
                                    <b class="required-mark">*</b>
                                @endif
                            </label>

                            <input class="form-control" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp" @if (empty($company->logo_path)) required @endif>
                            <small class="form-hint">
                                JPG, JPEG, PNG, or WEBP. Maximum size: 2 MB.
                                @if (!empty($company->logo_path))
                                    Leave empty to keep the existing logo.
                                @endif
                            </small>

                            @error('logo')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label for="social_media">Social Media Links <b class="required-mark">*</b></label>
                            <textarea class="form-control" id="social_media" name="social_media" rows="3" maxlength="2000"
                                required
                                placeholder="Enter one URL per line">{{ old('social_media', $company->social_media) }}</textarea>
                            <small class="form-hint">Enter one URL per line.</small>
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
                    <h2>Company Profile &amp; Services</h2>
                    <p>Describe your company and list the solar services you provide.</p>
                </div>

                <div class="panel-body">
                    <div class="form-grid company-profile-grid">
                        {{-- English Company Profile --}}
                        <div class="form-group">
                            <label for="companyEditor">
                                Company Profile <b class="required-mark">*</b>
                            </label>

                            <div class="editor-hint">
                                Describe the company, its solar services, experience, and areas served.
                            </div>

                            <div id="companyEditor">{!! old('description', $sanitizedDescription ?? '') !!}</div>

                            <input type="hidden" name="description" id="description"
                                placeholder="Enter your company English description"
                                value="{{ old('description', $company->description ?? '') }}" required>

                            @error('description')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Arabic Company Profile --}}
                        <div class="form-group">
                            <label for="companyEditorAr">
                                Company Profile (Arabic) <b class="required-mark">*</b>
                            </label>

                            <div class="editor-hint">
                                .اكتب نبذة عن الشركة وخدمات الطاقة الشمسية والخبرات والمناطق التي تغطيها
                            </div>

                            <div id="companyEditorAr">{!! $sanitizedArDescription ?? '' !!}</div>

                            <input type="hidden" name="ar_description" id="ar_description"
                                placeholder="Enter your company Arabic description"
                                value="{{ old('ar_description', $company->ar_description ?? '') }}" required>

                            @error('ar_description')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="services">Company Services (English) <b class="required-mark">*</b></label>
                            <textarea class="form-control" id="services" name="services" rows="4" maxlength="3000"
                                placeholder="Commercial solar, Hybrid systems, Off-grid systems"
                                required>{{ old('services', $servicesValue) }}</textarea>
                            <small class="form-hint">Separate each service with a comma.</small>
                            @error('services')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="ar_services">Company Services (Arabic) <b class="required-mark">*</b></label>
                            <textarea class="form-control" id="ar_services" name="ar_services" rows="4" maxlength="3000"
                                dir="rtl" required lang="ar"
                                placeholder="الطاقة الشمسية التجارية، الأنظمة الهجينة، الأنظمة خارج الشبكة">{{ old('ar_services', $arabicServicesValue) }}</textarea>
                            <small class="form-hint">افصل بين كل خدمة والأخرى بفاصلة.</small>
                            @error('ar_services')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- Account status is read-only for the company account --}}
            <section class="panel">
                <div class="panel-heading">
                    <h2>Account Status -- ReadOnly</h2>
                    <p>Approval and verification are managed by SunXFinder administrators.</p>
                </div>

                <div class="panel-body">
                    <div class="form-grid">

                        {{-- Approval Status --}}
                        <div class="form-group">
                            <label>Approval Status</label>
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
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                        </div>

                        {{-- Company Verification --}}
                        <div class="form-group">
                            <label>Company Verified</label>

                            <div>
                                @if ($company->is_verified)
                                    <span class="pill pill-green">Verified</span>
                                @else
                                    <span class="pill pill-yellow">Not verified</span>
                                @endif
                            </div>
                        </div>

                        {{-- Account Active --}}
                        <div class="form-group">
                            <label>Account Active</label>

                            <div>
                                @if ($company->is_active)
                                    <span class="pill pill-green">Active</span>
                                @else
                                    <span class="pill pill-red">Inactive</span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

            </section>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">Save Changes</button>
                @if ($company->is_verified && $company->status != 'pending')
                    <a href="{{ url('/companies/' . $company->slug) }}?lang={{ request('lang', app()->getLocale()) }}"
                        class="btn btn-light" target="_blank" rel="noopener noreferrer">
                        View on Website
                    </a>
                @endif

            </div>
        </form>
    </div>

    @push('scripts')
        <script src="{{ asset('main/js/script.js') }}"></script>
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
                        alert('Please select a valid image file.');
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