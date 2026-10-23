@extends('admin.layouts.app')
@section('title', 'Settings')
@section('breadcrumb', 'Settings')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Settings')
@section('page-description', 'App rules that apply to every customer.')

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

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </section>
    </form>
@endsection
