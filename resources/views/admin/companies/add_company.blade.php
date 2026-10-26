@extends('admin.layouts.app')

@php
    $isEditing = isset($company) && $company->exists;

    $pageTitle = $isEditing ? 'Update Company' : 'Add New Company';
    $pageBreadcrumb = $isEditing ? 'Update company' : 'Solar companies / Add new';

    $formAction = $isEditing ? route('admin.companies.update', $company->id) : route('admin.companies.store');
@endphp

@section('title', $pageTitle)
@section('breadcrumb', $pageBreadcrumb)
@section('eyebrow', 'PROVIDER DIRECTORY')
@section('page-title', $pageTitle)
@section('page-description',
    $isEditing
    ? 'Update the company profile, account settings, and login access.'
    : 'Register
    a solar provider on SunXFinder and configure its account access.')

@section('page-actions')
    <a href="{{ route('admin.companies.index') }}" class="btn btn-primary">
        ← Back to Companies
    </a>
@endsection

@section('content')

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error message --}}
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation errors --}}
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

    <form method="POST" action="{{ $formAction }}">
        @csrf

        @if ($isEditing)
            @method('PUT')
        @endif

        {{-- Company information --}}
        <section class="panel">
            <div class="panel-heading">
                <h2>Company Information</h2>
                <p>Enter the company's public profile and contact details.</p>
            </div>

            <div class="panel-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label for="name">Company Name <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="name" name="name" maxlength="200"
                            value="{{ old('name', $company->name ?? '') }}" placeholder="e.g. Sunshine Solar Solutions"
                            required>
                        @error('name')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="username">Company Username <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="username" name="username" maxlength="191"
                            value="{{ old('username', $company->username ?? '') }}" placeholder="e.g. sunshine_solar"
                            autocomplete="off" required>
                        @error('username')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <b style="color: red">*</b></label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="191" required
                            value="{{ old('email', $company->email ?? '') }}" placeholder="company@example.com">
                        @error('email')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="phone" name="phone" maxlength="30" required
                            value="{{ old('phone', $company->phone ?? '') }}" placeholder="09...">
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

                    <div class="form-group">
                        <label for="city">City <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="city" name="city" maxlength="100"
                            value="{{ old('city', $company->city ?? '') }}" placeholder="e.g. Port Sudan">
                        @error('city')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="license_number">
                            License Number
                            @if (!$isEditing || empty($company->license_number))
                                <b style="color: red">*</b>
                            @endif
                        </label>

                        <input class="form-control" type="text" id="license_number" name="license_number"
                            value="{{ old('license_number', $company->license_number ?? '') }}"
                            placeholder="Enter your company's official license number"
                            @if (!$isEditing || empty($company->license_number)) required @endif>

                        <small class="form-hint">
                            Enter your company's official license number.
                            @if ($isEditing && !empty($company->license_number))
                                Leave unchanged if you do not need to update it.
                            @endif
                        </small>

                        @error('license_number')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">
                            @if ($isEditing)
                                Change Password (Optional)
                            @else
                                Initial Password <b style="color: red">*</b>
                            @endif
                        </label>
                        <input class="form-control" type="password" id="password" name="password" minlength="8"
                            autocomplete="new-password"
                            placeholder="{{ $isEditing ? 'Leave blank to keep current password' : 'At least 8 characters' }}"
                            {{ $isEditing ? '' : 'required' }}>
                        @error('password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Company Services: English + Arabic --}}
                    @include('admin.partials.services-editor', [
                        'services' => $company->services ?? '',
                        'arServices' => $company->ar_services ?? '',
                    ])

                    {{-- Company Profiles: English + Arabic --}}
                    <div class="company-profile-grid">

                        <div class="form-group">
                            <label for="companyEditor">
                                Company Profile <b style="color: red">*</b>
                            </label>

                            <div class="editor-hint">
                                Describe the company, its solar services, experience, and areas served.
                            </div>

                            <div id="companyEditor">{!! old('description', $sanitizedDescription ?? '') !!}</div>

                            <input type="hidden" name="description" id="description"
                                value="{{ old('description', $company->description ?? '') }}" required>

                            @error('description')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="companyEditorAr">
                                Company Profile (Arabic) <b style="color: red">*</b>
                            </label>

                            <div class="editor-hint" dir="rtl" lang="ar">
                                اكتب نبذة عن الشركة وخدمات الطاقة الشمسية والخبرات والمناطق التي تغطيها.
                            </div>

                            <div id="companyEditorAr" dir="rtl">{!! old('ar_description', $sanitizedArDescription ?? '') !!}</div>

                            <input type="hidden" name="ar_description" id="ar_description"
                                value="{{ old('ar_description', $company->ar_description ?? '') }}" required>

                            @error('ar_description')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                    </div>


                </div>
            </div>
        </section>

        {{-- Account settings --}}
        <section class="panel">
            <div class="panel-heading">
                <h2>Account Settings</h2>
                <p>Configure approval, verification, and login access.</p>
            </div>

            <div class="panel-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label for="status">Approval Status <b style="color: red">*</b></label>
                        @php
                            $selectedStatus = old('status', $company->status ?? 'pending');
                        @endphp

                        <select class="select-control" id="status" name="status" required>
                            <option value="pending" @selected($selectedStatus === 'pending')>
                                Pending
                            </option>
                            <option value="approved" @selected($selectedStatus === 'approved')>
                                Approved
                            </option>
                            <option value="rejected" @selected($selectedStatus === 'rejected')>
                                Rejected
                            </option>
                        </select>

                        @error('status')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="is_verified">Company Verified <b style="color: red">*</b></label>
                        @php
                            $selectedVerified = (string) old('is_verified', (int) ($company->is_verified ?? false));
                        @endphp

                        <select class="select-control" id="is_verified" name="is_verified" required>
                            <option value="0" @selected($selectedVerified === '0')>
                                No
                            </option>
                            <option value="1" @selected($selectedVerified === '1')>
                                Yes
                            </option>
                        </select>

                        @error('is_verified')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="is_active">Account Active <b style="color: red">*</b></label>
                        @php
                            $selectedActive = (string) old('is_active', (int) ($company->is_active ?? true));
                        @endphp

                        <select class="select-control" id="is_active" name="is_active" required>
                            <option value="0" @selected($selectedActive === '0')>
                                No — Inactive
                            </option>
                            <option value="1" @selected($selectedActive === '1')>
                                Yes — Active
                            </option>
                        </select>

                        @error('is_active')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.companies.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        {{ $isEditing ? 'Save Changes' : 'Create Company' }}
                    </button>
                </div>
            </div>
        </section>
    </form>

    {{-- Quill editor scripts --}}
@section('scripts')
    <script src="{{ asset('main/js/script.js') }}"></script>
    <script src="{{ asset_v('main/js/services-editor.js') }}"></script>
@endsection


@endsection
