@extends('admin.layouts.app')

@section('title', 'Ad Sales')
@section('breadcrumb', 'Ad Sales')
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', 'Ad Sales')
@section('page-description', 'What each company bought, whether it paid, and how the ad performed.')

@section('page-actions')
    <a href="{{ route('admin.ad-sales.report') }}" class="btn btn-light">Monthly report</a>
    <a href="{{ route('admin.ad-sales.create') }}" class="btn btn-primary">+ Add sale</a>
@endsection

@section('content')
    @php
        $statusClasses = ['requested' => 'pill-orange', 'awaiting_payment' => 'pill-yellow', 'paid' => 'pill-green', 'cancelled' => 'pill-red'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-ad-sales',
        'title' => 'How ad sales work',
        'intro' => 'Each row is one ad a company asked for or bought. Companies can request ads from their portal, and you can add sales yourself.',
        'steps' => [
            '<strong>Price it</strong>: open a request, set the amount and dates, and mark it Awaiting payment.',
            '<strong>Record payment</strong>: when the transfer arrives, mark it Paid and add the receipt reference.',
            '<strong>Put the ad live</strong>: create the slide or carousel entry, then link it to the sale so its results appear here.',
        ],
        'note' => 'Marking a sale as Paid does not turn the ad on. You control that in App Slider or Company Carousel.',
    ])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="metrics-strip">
        <div><small>New requests</small><strong>{{ number_format($metrics['requested']) }}</strong></div>
        <div><small>Awaiting payment</small><strong>{{ number_format($metrics['awaiting_payment']) }}</strong></div>
        <div><small>Ending within {{ \App\Models\AdSale::ENDING_SOON_DAYS }} days</small><strong>{{ number_format($metrics['ending_soon']) }}</strong></div>
        <div>
            <small>Paid this month</small>
            <strong>
                @forelse ($metrics['paid_this_month'] as $currency => $total)
                    {{ number_format((float) $total) }} {{ $currency }}@if (!$loop->last) · @endif
                @empty
                    0
                @endforelse
            </strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Sales</h2>
                <p>Newest first. Results count only the sale's dates.</p>
            </div>

            <form method="GET" action="{{ route('admin.ad-sales.index') }}" class="table-filters">
                <select class="select-control" name="status" aria-label="Status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\AdSale::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="select-control" name="product" aria-label="Product">
                    <option value="">All products</option>
                    @foreach (\App\Models\AdSale::PRODUCTS as $value => $label)
                        <option value="{{ $value }}" @selected(request('product') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="select-control" name="company_id" aria-label="Company">
                    <option value="">All companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-light">Filter</button>
                @if (request()->hasAny(['status', 'product', 'company_id', 'ending']))
                    <a href="{{ route('admin.ad-sales.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Product</th>
                        <th>Dates</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Clicks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td><strong>{{ $sale->clientLabel() }}</strong></td>
                            <td>
                                {{ \App\Models\AdSale::PRODUCTS[$sale->product] ?? $sale->product }}
                                <small class="cell-note">{{ \App\Models\AdSale::PLATFORMS[$sale->platform] ?? $sale->platform }}</small>
                            </td>
                            <td>
                                {{ $sale->starts_on?->format('M d, Y') ?? '—' }} – {{ $sale->ends_on?->format('M d, Y') ?? '—' }}
                                @if ($sale->isEndingSoon())
                                    <span class="pill pill-orange">Ending soon</span>
                                @endif
                            </td>
                            <td>{{ $sale->amount !== null ? number_format((float) $sale->amount, 2) . ' ' . $sale->currency : '—' }}</td>
                            <td><span class="pill {{ $statusClasses[$sale->status] ?? 'pill-blue' }}">{{ \App\Models\AdSale::STATUSES[$sale->status] ?? $sale->status }}</span></td>
                            <td>{{ $sale->results ? number_format($sale->results['views']) : '—' }}</td>
                            <td>{{ $sale->results ? number_format($sale->results['clicks']) : '—' }}</td>
                            <td>
                                <div class="company-actions">
                                    <a class="btn btn-light" href="{{ route('admin.ad-sales.edit', $sale->id) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.ad-sales.destroy', $sale->id) }}"
                                        onsubmit="return confirm('Delete this sale? A copy is kept in Archives.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="dashboard-empty"><strong>No ad sales yet.</strong> Add your first sale, or wait for a company to request an ad.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $sales, 'label' => 'records'])
    </section>
@endsection
