@extends('admin.layouts.app')

@php
    $isHeader = $section?->isHeader() ?? false;
    $pageLabel = \App\Models\LegalSection::PAGES[$page];
    $heading = $isHeader ? 'Edit page header' : ($section ? 'Edit section' : 'Add section');
@endphp

@section('title', $heading)
@section('breadcrumb', 'Privacy & Terms / ' . $pageLabel)
@section('eyebrow', 'SYSTEM')
@section('page-title', $heading)
@section('page-description', $pageLabel . ': enter the text in both English and Arabic.')

@section('page-actions')
    <a href="{{ route('admin.legal.index', ['tab' => $page]) }}" class="btn btn-light">← {{ $pageLabel }}</a>
@endsection

@section('content')
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" id="legalForm"
        action="{{ $section ? route('admin.legal.update', $section->id) : route('admin.legal.store', $page) }}">
        @csrf
        @if ($section) @method('PUT') @endif

        @if ($isHeader)
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Top of the page</h2>
                        <p>The small label, the page title, the introduction and the date.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="eyebrow_en">Small label (English)</label>
                        <input type="text" id="eyebrow_en" name="eyebrow_en" maxlength="150" value="{{ old('eyebrow_en', $section->eyebrow_en) }}">
                    </div>
                    <div class="form-group">
                        <label for="eyebrow_ar">Small label (Arabic)</label>
                        <input type="text" id="eyebrow_ar" name="eyebrow_ar" maxlength="150" dir="rtl" value="{{ old('eyebrow_ar', $section->eyebrow_ar) }}">
                    </div>
                    <div class="form-group">
                        <label for="title_en">Page title (English) *</label>
                        <input type="text" id="title_en" name="title_en" maxlength="255" required value="{{ old('title_en', $section->title_en) }}">
                    </div>
                    <div class="form-group">
                        <label for="title_ar">Page title (Arabic) *</label>
                        <input type="text" id="title_ar" name="title_ar" maxlength="255" required dir="rtl" value="{{ old('title_ar', $section->title_ar) }}">
                    </div>
                    <div class="form-group">
                        <label for="body_en">Introduction (English) *</label>
                        <textarea id="body_en" name="body_en" rows="4" maxlength="1000" required>{{ old('body_en', $section->body_en) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="body_ar">Introduction (Arabic) *</label>
                        <textarea id="body_ar" name="body_ar" rows="4" maxlength="1000" required dir="rtl">{{ old('body_ar', $section->body_ar) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="last_updated">Last updated *</label>
                        <input type="date" id="last_updated" name="last_updated" required
                            value="{{ old('last_updated', $section->last_updated?->format('Y-m-d')) }}">
                        <span class="form-hint">Shown in both languages under the introduction.</span>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.legal.index', ['tab' => $page]) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </section>
        @else
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Section</h2>
                        <p>Numbers are added automatically, so do not type them in the title.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_en">Title (English) *</label>
                        <input type="text" id="title_en" name="title_en" maxlength="255" required value="{{ old('title_en', $section?->title_en) }}">
                    </div>
                    <div class="form-group">
                        <label for="title_ar">Title (Arabic) *</label>
                        <input type="text" id="title_ar" name="title_ar" maxlength="255" required dir="rtl" value="{{ old('title_ar', $section?->title_ar) }}">
                    </div>
                    <div class="form-group">
                        <label for="type">Display as *</label>
                        <select class="select-control" id="type" name="type" required>
                            <option value="section" @selected(old('type', $section?->type ?? 'section') === 'section')>Numbered section</option>
                            <option value="contact" @selected(old('type', $section?->type) === 'contact')>Contact box (dark, after the sections)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group full-width" data-legal-editor>
                        <label>Text (English) *</label>
                        <div class="legal-editor"></div>
                        <input type="hidden" name="body_en" value="{{ old('body_en', $section?->body_en) }}">
                    </div>
                    <div class="form-group full-width" data-legal-editor>
                        <label>Text (Arabic) *</label>
                        <div class="legal-editor" dir="rtl"></div>
                        <input type="hidden" name="body_ar" value="{{ old('body_ar', $section?->body_ar) }}">
                    </div>
                </div>

                <label class="toggle-card" for="is_active">
                    <span class="toggle-card-copy">
                        <strong>Show on the website</strong>
                        <small>When off, the section stays saved here but visitors do not see it.</small>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input class="toggle-input" type="checkbox" id="is_active" name="is_active" value="1"
                        @checked(old('is_active', $section ? (int) $section->is_active : 1))>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <label class="toggle-card" for="bump_date">
                    <span class="toggle-card-copy">
                        <strong>Set "Last updated" to today</strong>
                        <small>Turn this off for small fixes such as a typo.</small>
                    </span>
                    <input type="hidden" name="bump_date" value="0">
                    <input class="toggle-input" type="checkbox" id="bump_date" name="bump_date" value="1" @checked(old('bump_date', 1))>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <div class="form-actions">
                    <a href="{{ route('admin.legal.index', ['tab' => $page]) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">{{ $section ? 'Save changes' : 'Add section' }}</button>
                </div>
            </section>
        @endif
    </form>
@endsection

@section('scripts')
    <script>
        (function () {
            const form = document.getElementById('legalForm');

            document.querySelectorAll('[data-legal-editor]').forEach((field) => {
                const input = field.querySelector('input[type="hidden"]');
                const quill = new Quill(field.querySelector('.legal-editor'), {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ header: 3 }],
                            ['bold', 'italic', 'underline'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['link', 'clean'],
                        ],
                    },
                });

                if (input.value.trim()) {
                    quill.clipboard.dangerouslyPasteHTML(input.value);
                }

                form.addEventListener('submit', () => {
                    input.value = quill.getText().trim() === '' ? '' : quill.getSemanticHTML();
                });
            });
        })();
    </script>
@endsection
