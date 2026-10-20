@extends('admin.layouts.app')
@section('title', $entry ? 'Edit Carousel Entry' : 'Add Carousel Entry')
@section('breadcrumb', 'Company Carousel')
@section('eyebrow', 'HOMEPAGE MANAGEMENT')
@section('page-title', $entry ? 'Edit Carousel Entry' : 'Add Carousel Entry')
@section('page-description', 'Choose a company and configure its carousel placement, display duration, and schedule.')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>{{ $entry ? 'Carousel entry details' : 'New carousel entry' }}</h2>
                <p>Control which platform displays this company and how long it stays visible.</p>
            </div>
        </div>

        <form method="POST"
            action="{{ $entry ? route('admin.company-carousel.update', $entry->id) : route('admin.company-carousel.store') }}"
            style="padding:22px;" id="companyCarouselForm">
            @csrf
            @if ($entry)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label for="company_id">Company *</label>
                    <select class="form-control" id="company_id" name="company_id" required>
                        <option value="">Select a company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) old('company_id', $entry?->company_id) === (string) $company->id)>
                                {{ $company->name }} (#{{ $company->id }})
                            </option>
                        @endforeach
                    </select>
                    @error('company_id') <small class="text-danger">{{ $message }}</small> @enderror
                    @if ($companies->isEmpty())
                        <small class="text-danger">No companies found. Create or activate a company first.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label for="platform">Display on *</label>
                    <select class="form-control" id="platform" name="platform" required>
                        <option value="both" @selected(old('platform', $entry?->platform ?? 'both') === 'both')>
                            App and website
                        </option>
                        <option value="app" @selected(old('platform', $entry?->platform) === 'app')>
                            Mobile app
                        </option>
                        <option value="website" @selected(old('platform', $entry?->platform) === 'website')>
                            Website
                        </option>
                    </select>

                    @error('platform')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="sort_order">Display order *</label>
                    <input class="form-control" type="number" id="sort_order" name="sort_order" min="0" max="65535"
                        value="{{ old('sort_order', $entry?->sort_order ?? $nextSortOrder) }}" required>
                    <small class="text-muted">Lower numbers appear first.</small>
                    @error('sort_order')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="interval_seconds">Display duration (seconds) *</label>
                    <input class="form-control" type="number" id="interval_seconds" name="interval_seconds" min="1"
                        max="3600" value="{{ old('interval_seconds', $entry?->interval_seconds ?? 5) }}" required>
                    <small class="text-muted">How long this company card stays visible before the carousel advances.</small>
                    @error('interval_seconds') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="is_active">Status *</label>
                    <select class="form-control" id="is_active" name="is_active" required>
                        <option value="1" @selected((string) old('is_active', (int) ($entry?->is_active ?? true)) === '1')>
                            Active — show when scheduled</option>
                        <option value="0" @selected((string) old('is_active', (int) ($entry?->is_active ?? true)) === '0')>
                            Inactive — hidden</option>
                    </select>
                    @error('is_active') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <hr style="margin:26px 0;">

            <div style="margin-bottom:16px;">
                <h3 style="margin:0;">Optional schedule</h3>
                <p style="margin:5px 0 0;color:#8491a5;">Leave the start blank to make it available immediately, and leave
                    the end blank for no expiry.</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="starts_at">Start date and time</label>
                    <input class="form-control" type="datetime-local" id="starts_at" name="starts_at"
                        value="{{ old('starts_at', $entry?->starts_at?->format('Y-m-d\TH:i')) }}">
                    @error('starts_at') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <label for="ends_at">End date and time</label>
                    <input class="form-control" type="datetime-local" id="ends_at" name="ends_at"
                        value="{{ old('ends_at', $entry?->ends_at?->format('Y-m-d\TH:i')) }}">
                    @error('ends_at') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:26px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" @disabled($companies->isEmpty())>
                    {{ $entry ? 'Save Changes' : 'Add to Carousel' }}
                </button>
                <a href="{{ route('admin.company-carousel.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection