@extends('admin.layouts.app')

@section('title', $sale ? 'Edit ad sale' : 'Add ad sale')
@section('breadcrumb', 'Ad Sales')
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', $sale ? 'Edit ad sale' : 'Add ad sale')
@section('page-description', 'Record what was sold, its dates and the payment.')

@section('page-actions')
    <a href="{{ route('admin.ad-sales.index') }}" class="btn btn-light">&larr; Back to Ad Sales</a>
@endsection

@section('content')
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $sale ? route('admin.ad-sales.update', $sale->id) : route('admin.ad-sales.store') }}">
        @csrf
        @if ($sale)
            @method('PUT')
        @endif

        @if ($sale && ($sale->request_note || $sale->artwork_path || $sale->link_url))
            <section class="panel">
                <div class="panel-heading"><h2>From the company</h2><p>What the company sent with its request.</p></div>
                <div class="panel-body">
                    @if ($sale->request_note)<p style="white-space:pre-line">{{ $sale->request_note }}</p>@endif
                    @if ($sale->link_url)<p><a href="{{ $sale->link_url }}" target="_blank" rel="noopener">{{ $sale->link_url }}</a></p>@endif
                    @if ($sale->artwork_path)
                        <a href="{{ $sale->artworkUrl() }}" target="_blank" rel="noopener">
                            <img src="{{ $sale->artworkUrl() }}" alt="Artwork from the company" style="max-width:320px;border-radius:8px">
                        </a>
                    @endif
                </div>
            </section>
        @endif

        <section class="panel">
            <div class="panel-heading"><h2>Sale</h2><p>Choose a company, or type a name for a supplier or other advertiser.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_id">Company</label>
                        <select class="select-control" id="company_id" name="company_id">
                            <option value="">No company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('company_id', $sale?->company_id) === (string) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="client_name">Other client name</label>
                        <input class="form-control" id="client_name" name="client_name" maxlength="150" value="{{ old('client_name', $sale?->client_name) }}">
                    </div>
                    <div class="form-group">
                        <label for="product">Product *</label>
                        <select class="select-control" id="product" name="product" required>
                            @foreach (\App\Models\AdSale::PRODUCTS as $value => $label)
                                <option value="{{ $value }}" @selected(old('product', $sale?->product) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="platform">Shown on *</label>
                        <select class="select-control" id="platform" name="platform" required>
                            @foreach (\App\Models\AdSale::PLATFORMS as $value => $label)
                                <option value="{{ $value }}" @selected(old('platform', $sale?->platform ?? 'app') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="starts_on">Start date</label>
                        <input class="form-control" type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $sale?->starts_on?->toDateString()) }}">
                    </div>
                    <div class="form-group">
                        <label for="ends_on">End date</label>
                        <input class="form-control" type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $sale?->ends_on?->toDateString()) }}">
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>Link to the ad</h2><p>Linking a slide or carousel entry shows its views and clicks on this sale and to the company.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="app_slider_id">Slide</label>
                        <select class="select-control" id="app_slider_id" name="app_slider_id">
                            <option value="">None</option>
                            @foreach ($sliders as $slider)
                                <option value="{{ $slider->id }}" @selected((string) old('app_slider_id', $sale?->app_slider_id) === (string) $slider->id)>{{ $slider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="company_carousel_id">Carousel entry</label>
                        <select class="select-control" id="company_carousel_id" name="company_carousel_id">
                            <option value="">None</option>
                            @foreach ($carousels as $carousel)
                                <option value="{{ $carousel->id }}" @selected((string) old('company_carousel_id', $sale?->company_carousel_id) === (string) $carousel->id)>
                                    {{ $carousel->company?->name ?? 'Carousel #' . $carousel->id }} ({{ \App\Models\AdSale::PLATFORMS[$carousel->platform] ?? $carousel->platform }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>Payment</h2><p>The company sees the amount and status, but never your notes or the reference.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input class="form-control" type="number" id="amount" name="amount" min="0" step="0.01" value="{{ old('amount', $sale?->amount) }}">
                    </div>
                    <div class="form-group">
                        <label for="currency">Currency *</label>
                        <select class="select-control" id="currency" name="currency" required>
                            @foreach (['SDG', 'USD'] as $currency)
                                <option value="{{ $currency }}" @selected(old('currency', $sale?->currency ?? 'SDG') === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select class="select-control" id="status" name="status" required>
                            @foreach (\App\Models\AdSale::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $sale?->status ?? 'awaiting_payment') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_reference">Payment reference</label>
                        <input class="form-control" id="payment_reference" name="payment_reference" maxlength="150" value="{{ old('payment_reference', $sale?->payment_reference) }}">
                    </div>
                    <div class="form-group">
                        <label for="admin_notes">Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" maxlength="2000">{{ old('admin_notes', $sale?->admin_notes) }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <div class="form-actions">
            <a href="{{ route('admin.ad-sales.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Save sale</button>
        </div>
    </form>
@endsection
