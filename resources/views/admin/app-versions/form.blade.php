@extends('admin.layouts.app')

@php
    $isEditing = isset($version) && $version?->exists;

    $pageTitle = $isEditing ? 'Update App Version' : 'Add App Version';
    $pageBreadcrumb = $isEditing ? 'App Versions / Update version' : 'App Versions / Add new';

    $formAction = $isEditing ? route('admin.app-versions.update', $version->id) : route('admin.app-versions.store');

    $selectedPlatform = old('platform', $version->platform ?? '');
    $forceUpdate = (bool) old('force_update', $version->force_update ?? false);
@endphp

@section('title', $pageTitle)
@section('breadcrumb', $pageBreadcrumb)
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', $pageTitle)
@section('page-description', 'Configure the minimum supported version and update link for the Android and iOS apps.')

@section('page-actions')
    <a href="{{ route('admin.app-versions.index') }}" class="btn btn-primary">
        ← Back to App Versions
    </a>
@endsection

@section('content')

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
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

    <form method="POST" action="{{ $formAction }}">
        @csrf

        @if ($isEditing)
            @method('PUT')
        @endif

        {{-- Version configuration --}}
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Version Configuration</h2>
                    <p>Choose the platform, the oldest version still allowed, and where users download updates.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label for="platform">Platform <b style="color: red">*</b></label>
                        <select class="select-control" id="platform" name="platform" required>
                            <option value="" disabled @selected($selectedPlatform === '')>Select platform</option>
                            @foreach (\App\Models\AppVersion::PLATFORMS as $value => $label)
                                <option value="{{ $value }}" @selected($selectedPlatform === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('platform')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="minimum_version">Minimum Version <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="minimum_version" name="minimum_version"
                            maxlength="20" inputmode="decimal" pattern="\d+(\.\d+){0,3}"
                            value="{{ old('minimum_version', $version->minimum_version ?? '') }}" placeholder="e.g. 1.6.2"
                            required>
                        <small class="form-hint">Users on an older version will be asked to update.</small>
                        @error('minimum_version')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group full-width">
                        <label for="app_url">App Store / Play Store URL <b style="color: red">*</b></label>
                        <input class="form-control" type="url" id="app_url" name="app_url" maxlength="500"
                            value="{{ old('app_url', $version->app_url ?? '') }}"
                            placeholder="https://play.google.com/store/apps/details?id=... or https://apps.apple.com/..."
                            required>
                        @error('app_url')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                </div>
            </div>
        </section>

        {{-- Update policy --}}
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Update Policy</h2>
                    <p>Decide whether outdated apps can still be used.</p>
                </div>
            </div>

            <div class="panel-body">
                <label class="toggle-card" for="force_update">
                    <span class="toggle-card-copy">
                        <strong>Force Update</strong>
                        <small>Prevent users from continuing until they update to the minimum version.
                            When off, they see an update prompt they can dismiss.</small>
                    </span>

                    {{-- Unchecked checkboxes are not submitted; send 0 by default. --}}
                    <input type="hidden" name="force_update" value="0">
                    <input class="toggle-input" type="checkbox" id="force_update" name="force_update" value="1"
                        @checked($forceUpdate)>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>
                @error('force_update')
                    <small class="field-error">{{ $message }}</small>
                @enderror

                <div class="form-grid app-version-messages">
                    <div class="form-group">
                        <label for="description_en">Update Message (English)</label>
                        <textarea class="form-control" id="description_en" name="description_en" rows="3"
                            maxlength="500" placeholder="e.g. A new version is available with faster quotes and bug fixes.">{{ old('description_en', $version->description_en ?? '') }}</textarea>
                        <small class="form-hint">Shown to users whose app is in English. Leave empty for the default
                            message.</small>
                        @error('description_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description_ar">Update Message (Arabic)</label>
                        <textarea class="form-control" id="description_ar" name="description_ar" rows="3"
                            maxlength="500" dir="rtl" lang="ar" placeholder="مثال: يتوفر إصدار جديد مع تحسينات وإصلاحات.">{{ old('description_ar', $version->description_ar ?? '') }}</textarea>
                        <small class="form-hint">Shown to users whose app is in Arabic. Leave empty for the default
                            message.</small>
                        @error('description_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.app-versions.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        {{ $isEditing ? 'Save Changes' : 'Save App Version' }}
                    </button>
                </div>
            </div>
        </section>
    </form>
@endsection
