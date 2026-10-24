@extends('admin.layouts.app')

@php
    $isEditing = isset($question) && $question?->exists;

    $pageTitle = $isEditing ? 'Update Rating Question' : 'Add Rating Question';
    $pageBreadcrumb = $isEditing ? 'Rating Questions / Update question' : 'Rating Questions / Add new';

    $formAction = $isEditing
        ? route('admin.rating-questions.update', $question->id)
        : route('admin.rating-questions.store');

    $isActive = (bool) old('is_active', $question->is_active ?? true);
@endphp

@section('title', $pageTitle)
@section('breadcrumb', $pageBreadcrumb)
@section('eyebrow', 'CUSTOMER FEEDBACK')
@section('page-title', $pageTitle)
@section('page-description', 'Customers answer this question on the 1–4 scale when they rate a company.')

@section('page-actions')
    <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-primary">
        ← Back to Rating Questions
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

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Question</h2>
                    <p>A short title (shown as the label) and an optional full question, in both languages.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_en">Title (English) <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="title_en" name="title_en" maxlength="100"
                            value="{{ old('title_en', $question->title_en ?? '') }}" placeholder="e.g. Price" required>
                        @error('title_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="title_ar">Title (Arabic) <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="title_ar" name="title_ar" maxlength="100" dir="rtl"
                            lang="ar" value="{{ old('title_ar', $question->title_ar ?? '') }}" placeholder="مثال: السعر"
                            required>
                        @error('title_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="question_en">Question (English)</label>
                        <input class="form-control" type="text" id="question_en" name="question_en" maxlength="255"
                            value="{{ old('question_en', $question->question_en ?? '') }}"
                            placeholder="e.g. How was the price?">
                        @error('question_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="question_ar">Question (Arabic)</label>
                        <input class="form-control" type="text" id="question_ar" name="question_ar" maxlength="255"
                            dir="rtl" lang="ar" value="{{ old('question_ar', $question->question_ar ?? '') }}"
                            placeholder="مثال: كيف كان السعر؟">
                        @error('question_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Display Order</label>
                        <input class="form-control" type="number" id="sort_order" name="sort_order" min="0"
                            max="999" value="{{ old('sort_order', $question->sort_order ?? 0) }}">
                        <small class="form-hint">Lower numbers show first in the app.</small>
                        @error('sort_order')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Visibility</h2>
                    <p>Turn a question off to stop asking it without losing its past answers.</p>
                </div>
            </div>

            <div class="panel-body">
                <label class="toggle-card" for="is_active">
                    <span class="toggle-card-copy">
                        <strong>Show in the app</strong>
                        <small>When off, customers are no longer asked this question.</small>
                    </span>

                    {{-- Unchecked checkboxes are not submitted; send 0 by default. --}}
                    <input type="hidden" name="is_active" value="0">
                    <input class="toggle-input" type="checkbox" id="is_active" name="is_active" value="1"
                        @checked($isActive)>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <div class="form-actions">
                    <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        {{ $isEditing ? 'Save Changes' : 'Save Question' }}
                    </button>
                </div>
            </div>
        </section>
    </form>
@endsection
