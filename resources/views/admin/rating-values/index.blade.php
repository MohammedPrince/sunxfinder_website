@extends('admin.layouts.app')
@section('title', 'Rating Values')
@section('breadcrumb', 'Rating Values')
@section('eyebrow', 'CUSTOMER FEEDBACK')
@section('page-title', 'Rating Values')
@section('page-description', 'The answer scale for rating questions, from 1 (lowest) to 4 (highest).')
@section('page-actions')
    <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-light">← Rating questions</a>
@endsection

@section('content')
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

    <form method="POST" action="{{ route('admin.rating-values.update') }}">
        @csrf
        @method('PUT')

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Answer labels</h2>
                    <p>Rename how each point is shown in the app. The scale stays 1–4, so averages stay comparable.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="rating-value-grid">
                    @foreach ($values as $value)
                        <div class="rating-value-row">
                            <span class="rating-value-badge">{{ $value->value }}</span>

                            <div class="form-group">
                                <label for="label_en_{{ $value->value }}">English label <b style="color: red">*</b></label>
                                <input class="form-control" type="text" id="label_en_{{ $value->value }}"
                                    name="values[{{ $value->value }}][label_en]" maxlength="50" required
                                    value="{{ old("values.{$value->value}.label_en", $value->label_en) }}">
                                @error("values.{$value->value}.label_en")
                                    <small class="field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="label_ar_{{ $value->value }}">Arabic label <b style="color: red">*</b></label>
                                <input class="form-control" type="text" id="label_ar_{{ $value->value }}"
                                    name="values[{{ $value->value }}][label_ar]" maxlength="50" dir="rtl" lang="ar"
                                    required value="{{ old("values.{$value->value}.label_ar", $value->label_ar) }}">
                                @error("values.{$value->value}.label_ar")
                                    <small class="field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Values</button>
                </div>
            </div>
        </section>
    </form>
@endsection
