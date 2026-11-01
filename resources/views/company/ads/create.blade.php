@extends('company.layouts.app')

@section('title', __('company/messages.ads.request_title'))
@section('breadcrumb', __('company/messages.ads.title'))
@section('eyebrow', __('company/messages.ads.eyebrow'))
@section('page-title', __('company/messages.ads.request_title'))
@section('page-description', __('company/messages.ads.request_description'))

@section('page-actions')
    <a href="{{ route('company.ads.index') }}" class="btn btn-light">{{ __('company/messages.ads.back') }}</a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>{{ __('company/messages.common.fix_errors') }}</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.ads.store') }}">
        @csrf

        <section class="panel">
            <div class="panel-heading">
                <h2>{{ __('company/messages.ads.request_details') }}</h2>
                <p>{{ __('company/messages.ads.request_details_sub') }}</p>
            </div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="product">{{ __('company/messages.ads.product') }} <b class="required-mark">*</b></label>
                        {{-- Companies can only request the home slider for now.
                        <select class="select-control" id="product" name="product" required>
                            @foreach (\App\Models\AdSale::REQUESTABLE_PRODUCTS as $value)
                                <option value="{{ $value }}" @selected(old('product') === $value)>{{ __('company/messages.ads.products.' . $value) }}</option>
                            @endforeach
                        </select>
                        --}}
                        <input type="hidden" name="product" value="slider">
                        <input class="form-control" type="text" id="product" value="{{ __('company/messages.ads.products.slider') }}" readonly>
                        @error('product')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="platform">{{ __('company/messages.ads.shown_on') }} <b class="required-mark">*</b></label>
                        {{-- Company requests are for the app only for now.
                        <select class="select-control" id="platform" name="platform" required>
                            @foreach (['app', 'website', 'both'] as $value)
                                <option value="{{ $value }}" @selected(old('platform', 'both') === $value)>{{ __('company/messages.ads.platforms.' . $value) }}</option>
                            @endforeach
                        </select>
                        --}}
                        <input type="hidden" name="platform" value="app">
                        <input class="form-control" type="text" id="platform" value="{{ __('company/messages.ads.platforms.app') }}" readonly>
                        @error('platform')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="starts_on">{{ __('company/messages.ads.start_date') }} <b class="required-mark">*</b></label>
                        <input class="form-control" type="date" id="starts_on" name="starts_on" min="{{ today()->toDateString() }}"
                            value="{{ old('starts_on', today()->addWeek()->toDateString()) }}" required>
                        @error('starts_on')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="weeks">{{ __('company/messages.ads.weeks') }} <b class="required-mark">*</b></label>
                        <input class="form-control" type="number" id="weeks" name="weeks" min="1" max="52" value="{{ old('weeks', 1) }}" required>
                        @error('weeks')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="link_url">{{ __('company/messages.ads.link') }}</label>
                        <input class="form-control" type="url" id="link_url" name="link_url" maxlength="500" dir="ltr"
                            placeholder="https://" value="{{ old('link_url') }}">
                        @error('link_url')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    {{-- Only admin adds ad images for now.
                    <div class="form-group">
                        <label for="artwork">{{ __('company/messages.ads.artwork') }}</label>
                        <input class="form-control" type="file" id="artwork" name="artwork" accept=".jpg,.jpeg,.png,.webp">
                        <small class="form-hint">{{ __('company/messages.ads.artwork_hint') }}</small>
                        @error('artwork')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    --}}
                    <div class="form-group">
                        <label for="request_note">{{ __('company/messages.ads.note') }}</label>
                        <textarea class="form-control" id="request_note" name="request_note" rows="4" maxlength="1000"
                            placeholder="{{ __('company/messages.ads.note_placeholder') }}">{{ old('request_note') }}</textarea>
                        @error('request_note')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <div class="form-actions">
            <a href="{{ route('company.ads.index') }}" class="btn btn-light">{{ __('company/messages.common.cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('company/messages.ads.send_request') }}</button>
        </div>
    </form>
@endsection
