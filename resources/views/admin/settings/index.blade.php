@extends('admin.layouts.app')
@section('title', 'Settings')
@section('breadcrumb', 'Settings')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Settings')
@section('page-description', 'App rules and website maintenance mode.')

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

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Quote requests</h2>
                    <p>Limits on what customers can send from the app.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="max_open_quotes">Open requests per customer <b style="color: red">*</b></label>
                        <input class="form-control" type="number" id="max_open_quotes" name="max_open_quotes"
                            min="1" max="50" required
                            value="{{ old('max_open_quotes', $settings['max_open_quotes']) }}">
                        <small class="form-hint">
                            A request is open until the customer accepts an offer. At the limit, the app blocks sending
                            new requests and the server rejects them.
                        </small>
                        @error('max_open_quotes')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

            </div>
        </section>

        {{-- Website maintenance mode --}}
        @php
            $maintenance = $settings['maintenance'];
            $maintenanceOn = (bool) old('maintenance_enabled', $maintenance['enabled']);
            $until = old('maintenance_until', $maintenance['until'] ? \Illuminate\Support\Carbon::parse($maintenance['until'])->format('Y-m-d\TH:i') : '');
        @endphp
        <section class="panel {{ $maintenance['enabled'] ? 'maintenance-on' : '' }}">
            <div class="panel-heading">
                <div>
                    <h2>
                        Website maintenance mode
                        <span class="pill {{ $maintenance['enabled'] ? 'pill-red' : 'pill-green' }}">
                            {{ $maintenance['enabled'] ? 'ON: website closed' : 'OFF: website live' }}
                        </span>
                    </h2>
                    <p>
                        Visitors see a maintenance page instead of the website (HTTP 503). You still see the website
                        while signed in as admin. The admin and company panels, the mobile app and Terms/Privacy keep
                        working.
                    </p>
                </div>
            </div>

            <div class="panel-body">
                <label class="toggle-card" for="maintenance_enabled">
                    <span class="toggle-card-copy">
                        <strong>Show the maintenance page</strong>
                        <small>Turn on before big updates; turn off when you are done.</small>
                    </span>
                    <input type="hidden" name="maintenance_enabled" value="0">
                    <input class="toggle-input" type="checkbox" id="maintenance_enabled" name="maintenance_enabled"
                        value="1" @checked($maintenanceOn)>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <div class="form-grid" style="margin-top:18px">
                    <div class="form-group">
                        <label for="maintenance_title_en">Title (English)</label>
                        <input class="form-control" type="text" id="maintenance_title_en" name="maintenance_title_en"
                            maxlength="150" placeholder="e.g. We will be back soon"
                            value="{{ old('maintenance_title_en', $maintenance['title_en']) }}">
                        @error('maintenance_title_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_title_ar">Title (Arabic)</label>
                        <input class="form-control" type="text" id="maintenance_title_ar" name="maintenance_title_ar"
                            maxlength="150" dir="rtl" lang="ar" placeholder="مثال: الموقع تحت الصيانة"
                            value="{{ old('maintenance_title_ar', $maintenance['title_ar']) }}">
                        @error('maintenance_title_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_message_en">Message (English)</label>
                        <textarea class="form-control" id="maintenance_message_en" name="maintenance_message_en" rows="4"
                            maxlength="2000" placeholder="e.g. We are upgrading SunXFinder to serve you better. Please check back in a few hours.">{{ old('maintenance_message_en', $maintenance['message_en']) }}</textarea>
                        @error('maintenance_message_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_message_ar">Message (Arabic)</label>
                        <textarea class="form-control" id="maintenance_message_ar" name="maintenance_message_ar" rows="4"
                            maxlength="2000" dir="rtl" lang="ar" placeholder="مثال: نعمل على تحديث SunXFinder لخدمتك بشكل أفضل. يرجى العودة بعد بضع ساعات.">{{ old('maintenance_message_ar', $maintenance['message_ar']) }}</textarea>
                        @error('maintenance_message_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_until">Expected back (optional)</label>
                        <input class="form-control" type="datetime-local" id="maintenance_until" name="maintenance_until"
                            value="{{ $until }}">
                        <small class="form-hint">Shown on the maintenance page while it is in the future.</small>
                        @error('maintenance_until')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-light">View website ↗</a>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </section>
    </form>
@endsection
