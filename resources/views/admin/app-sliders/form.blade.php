@extends('admin.layouts.app')

@section('title', $slider ? 'Edit slide' : 'Add slide')
@section('breadcrumb', 'App Slider / ' . ($slider ? 'Edit' : 'Add'))
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', $slider ? 'Edit slide' : 'Add slide')
@section('page-description', 'Configure an ad or announcement for the slider on the app Home screen.')

@section('page-actions')
    <a href="{{ route('admin.app-sliders.index') }}" class="btn btn-light">← All slides</a>
@endsection

@section('content')
    @php
        $hasLink = (bool) old('has_link', $slider ? (int) filled($slider->link_url) : 0);
        $isActive = (bool) old('is_active', $slider ? (int) $slider->is_active : 1);
        $interval = old('interval_seconds', $slider?->interval_seconds ?? 5);
    @endphp

    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" id="sliderForm"
        action="{{ $slider ? route('admin.app-sliders.update', $slider->id) : route('admin.app-sliders.store') }}">
        @csrf
        @if ($slider) @method('PUT') @endif

        <div class="slider-form-layout">
            <div class="slider-form-main">
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Slide content</h2>
                            <p>The image is required. Descriptions are optional captions shown over the image.</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="name">Slide name *</label>
                            <input type="text" id="name" name="name" maxlength="120" required
                                value="{{ old('name', $slider?->name) }}" placeholder="e.g. Summer panel offer">
                            <span class="form-hint">For admins, and used as the image description for screen readers in the app.</span>
                        </div>

                        <div class="form-group full-width">
                            <label for="image">Slide image {{ $slider ? '' : '*' }}</label>
                            <label class="slider-dropzone" for="image">
                                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" @required(!$slider)>
                                <strong>{{ $slider ? 'Choose a new image to replace the current one' : 'Choose an image' }}</strong>
                                <small>JPG, PNG or WebP, up to 4 MB, at least 600 × 200 px. Recommended 1280 × 640 px (2:1).</small>
                                <small class="slider-file-name" id="sliderFileName" hidden></small>
                            </label>
                            @if ($slider)
                                <span class="form-hint">Current image: {{ $slider->image_width ?? '?' }} × {{ $slider->image_height ?? '?' }} px.</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="description_en">Description (English)</label>
                            <textarea id="description_en" name="description_en" rows="3" maxlength="255"
                                placeholder="e.g. Save 15% on solar panels this month">{{ old('description_en', $slider?->description_en) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="description_ar">Description (Arabic)</label>
                            <textarea id="description_ar" name="description_ar" rows="3" maxlength="255" dir="rtl"
                                placeholder="مثال: وفّر 15٪ على الألواح الشمسية هذا الشهر">{{ old('description_ar', $slider?->description_ar) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Behaviour</h2>
                            <p>What happens when the customer taps the slide, and how long it stays.</p>
                        </div>
                    </div>

                    <label class="toggle-card" for="has_link">
                        <span class="toggle-card-copy">
                            <strong>Slide opens a link</strong>
                            <small>When off, the slide is only an image and tapping it does nothing.</small>
                        </span>
                        <input type="hidden" name="has_link" value="0">
                        <input class="toggle-input" type="checkbox" id="has_link" name="has_link" value="1" @checked($hasLink)>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="form-group slider-link-field" id="sliderLinkField" @if (!$hasLink) hidden @endif>
                        <label for="link_url">Link *</label>
                        <input type="text" id="link_url" name="link_url" maxlength="500" inputmode="url"
                            value="{{ old('link_url', $slider?->link_url) }}" placeholder="https://… or sunxfinder://company/company-slug">
                        <span class="form-hint">Use https:// for a website, or sunxfinder://company/{slug} to open a company inside the app.</span>
                    </div>

                    <div class="form-grid slider-behaviour-grid">
                        <div class="form-group">
                            <label for="interval_seconds">Display time (seconds) *</label>
                            <input type="number" id="interval_seconds" name="interval_seconds" min="2" max="60" required value="{{ $interval }}">
                            <span class="form-hint">How long the slide stays before the next one. 4–6 seconds reads well.</span>
                        </div>

                        <div class="form-group">
                            <label for="sort_order">Display order *</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" max="65535" required
                                value="{{ old('sort_order', $slider?->sort_order ?? $nextSortOrder) }}">
                            <span class="form-hint">Lower numbers appear first.</span>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Status and schedule</h2>
                            <p>Leave the dates empty to show the slide right away with no end date.</p>
                        </div>
                    </div>

                    <label class="toggle-card" for="is_active">
                        <span class="toggle-card-copy">
                            <strong>Active</strong>
                            <small>Inactive slides are never shown in the app, whatever the schedule says.</small>
                        </span>
                        <input type="hidden" name="is_active" value="0">
                        <input class="toggle-input" type="checkbox" id="is_active" name="is_active" value="1" @checked($isActive)>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="form-grid slider-behaviour-grid">
                        <div class="form-group">
                            <label for="starts_at">Start date and time</label>
                            <input type="datetime-local" id="starts_at" name="starts_at"
                                value="{{ old('starts_at', $slider?->starts_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="form-group">
                            <label for="ends_at">End date and time</label>
                            <input type="datetime-local" id="ends_at" name="ends_at"
                                value="{{ old('ends_at', $slider?->ends_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('admin.app-sliders.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">{{ $slider ? 'Save changes' : 'Add slide' }}</button>
                    </div>
                </section>
            </div>

            <aside class="slider-form-side">
                <section class="panel slider-preview-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>App preview</h2>
                            <p>Approximately how the slide looks on the Home screen.</p>
                        </div>
                    </div>

                    <div class="slider-preview-phone">
                        <div class="slider-preview" id="sliderPreview">
                            <img id="sliderPreviewImage" src="{{ $slider?->imageUrl() }}" alt="" @if (!$slider) hidden @endif>
                            <span class="slider-preview-empty" id="sliderPreviewEmpty" @if ($slider) hidden @endif>Your image appears here</span>
                            <div class="slider-preview-caption" id="sliderPreviewCaption">
                                <span id="sliderPreviewText"></span>
                                <span class="slider-preview-link" id="sliderPreviewLink" @if (!$hasLink) hidden @endif>Tap to open ›</span>
                            </div>
                            <div class="slider-preview-progress"><span id="sliderPreviewBar"></span></div>
                        </div>
                        <div class="slider-preview-dots" aria-hidden="true"><i class="is-active"></i><i></i><i></i></div>
                    </div>

                    <div class="slider-preview-lang" role="group" aria-label="Preview language">
                        <button type="button" class="is-active" data-lang="en">English</button>
                        <button type="button" data-lang="ar">العربية</button>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const imageInput = $('image'), previewImage = $('sliderPreviewImage'), empty = $('sliderPreviewEmpty');
            const fileName = $('sliderFileName'), hasLink = $('has_link'), linkField = $('sliderLinkField');
            const text = $('sliderPreviewText'), caption = $('sliderPreviewCaption'), bar = $('sliderPreviewBar');
            const descEn = $('description_en'), descAr = $('description_ar'), interval = $('interval_seconds');
            let lang = 'en', objectUrl = null;

            imageInput.addEventListener('change', () => {
                const file = imageInput.files[0];
                if (!file) return;
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = URL.createObjectURL(file);
                previewImage.src = objectUrl;
                previewImage.hidden = false;
                empty.hidden = true;
                fileName.hidden = false;
                fileName.textContent = file.name + ' · ' + (file.size / 1048576).toFixed(2) + ' MB';
            });

            const syncLink = () => {
                linkField.hidden = !hasLink.checked;
                $('sliderPreviewLink').hidden = !hasLink.checked;
            };
            hasLink.addEventListener('change', syncLink);

            const syncText = () => {
                const value = (lang === 'ar' ? descAr.value : descEn.value).trim();
                text.textContent = value;
                caption.dir = lang === 'ar' ? 'rtl' : 'ltr';
                caption.hidden = !value && !hasLink.checked;
            };
            [descEn, descAr].forEach((el) => el.addEventListener('input', syncText));
            hasLink.addEventListener('change', syncText);

            document.querySelectorAll('.slider-preview-lang button').forEach((button) => {
                button.addEventListener('click', () => {
                    lang = button.dataset.lang;
                    document.querySelectorAll('.slider-preview-lang button')
                        .forEach((b) => b.classList.toggle('is-active', b === button));
                    syncText();
                });
            });

            // The progress line runs for the display time, like the slide timer in the app.
            const syncTimer = () => {
                const seconds = Math.min(60, Math.max(2, parseInt(interval.value, 10) || 5));
                bar.style.animationDuration = seconds + 's';
            };
            interval.addEventListener('input', syncTimer);

            syncLink();
            syncText();
            syncTimer();
        })();
    </script>
@endsection
