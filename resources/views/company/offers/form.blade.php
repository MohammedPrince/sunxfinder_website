@extends('company.layouts.app')

@php
    // Editing, a copy of an earlier offer (?from=), or a new one.
    $base = $offer ?? $source;
    $pageTitle = $offer ? __('company/messages.package_offers.edit') : __('company/messages.package_offers.create');
@endphp

@section('title', $pageTitle)
@section('breadcrumb', __('company/messages.package_offers.title'))
@section('eyebrow', __('company/messages.package_offers.eyebrow'))
@section('page-title', $pageTitle)
@section('page-description', __('company/messages.package_offers.form_description'))

@push('styles')
    <link rel="stylesheet" href="{{ asset_v('css/package-offer-builder.css') }}">
@endpush

@section('page-actions')
    <a href="{{ $offer ? route('company.offers.show', $offer->id) : route('company.offers.index') }}" class="btn btn-light">
        {{ __('company/messages.package_offers.back') }}
    </a>
@endsection

@section('content')
    @include('company.offers.partials.alerts')

    @if ($source)
        <div class="alert alert-info">{{ __('company/messages.package_offers.copy_note', ['title' => $source->title_en]) }}
        </div>
    @endif

    @include('company.partials.guide', ['page' => 'package_offer_form'])

    <form method="POST" id="packageOfferForm" enctype="multipart/form-data"
        action="{{ $offer ? route('company.offers.update', $offer->id) : route('company.offers.store') }}">
        @csrf
        @if ($offer)
            @method('PUT')
        @endif

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.package_offers.section_details') }}</h2>
                    <p>{{ __('company/messages.package_offers.section_details_sub') }}</p>
                </div>
            </div>
            <div class="panel-body">
                @include('partials.package-offer-builder', ['base' => $base, 'products' => $products])
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.package_offers.image') }}</h2>
                    <p>{{ __('company/messages.package_offers.image_hint') }}</p>
                </div>
            </div>
            <div class="panel-body">
                @if ($offer?->image_path)
                    <div class="pkg-image-current">
                        <img src="{{ $offer->imageUrl() }}" alt="">
                        <label><input type="checkbox" name="package[remove_image]" value="1">
                            {{ __('company/messages.package_offers.remove_image') }}</label>
                    </div>
                @elseif ($source?->image_path)
                    <p class="form-hint">{{ __('company/messages.package_offers.copy_image_note') }}</p>
                @endif
                <label class="slider-dropzone" for="image">
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    <strong>{{ __('company/messages.package_offers.choose_image') }}</strong>
                    <small>{{ __('company/messages.package_offers.image_size') }}</small>
                </label>
                <div class="image-resize-info" id="offerImageInfo" aria-live="polite" hidden></div>
                @error('image')<small class="field-error">{{ $message }}</small>@enderror
            </div>
        </section>

        <div class="offer-notice">
            <p>{{ __('company/messages.package_offers.save_notice') }}</p>
        </div>

        <div class="form-actions">
            <a href="{{ $offer ? route('company.offers.show', $offer->id) : route('company.offers.index') }}"
                class="btn btn-light">{{ __('company/messages.common.cancel') }}</a>
            <button type="submit" class="btn btn-primary"
                id="submitOfferButton">{{ __('company/messages.package_offers.save') }}</button>
        </div>
    </form>

    <script src="{{ asset_v('admin/js/image-resizer.js') }}"></script>
    @include('partials.package-offer-builder-script')
    <script>
        (function () {
            const submit = document.getElementById('submitOfferButton');
            const form = document.getElementById('packageOfferForm');

            // Resized in the browser to the 1200 px the server stores.
            window.AdminImageResizer?.attach({
                input: document.getElementById('image'),
                info: document.getElementById('offerImageInfo'),
                submit: submit,
                maxBytes: 4 * 1024 * 1024,
                targetWidth: 1200,
                ratio: 2,
            });

            form.addEventListener('pkg:change', (event) => { submit.disabled = !event.detail.valid; });
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.textContent = @json(__('company/messages.quote.saving'));
            });
            window.PackageOfferBuilder.recalculate(form.querySelector('[data-package-builder]'));
        })();
    </script>
@endsection