@extends('admin.layouts.app')

@section('title', 'Ad Report')
@section('breadcrumb', 'Ad Sales')
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', 'Monthly ad report')
@section('page-description', 'Views, clicks and money received for one month.')

@section('page-actions')
    <a href="{{ route('admin.ad-sales.index') }}" class="btn btn-light">&larr; Back to Ad Sales</a>
    <button type="button" class="btn btn-light no-print" onclick="window.print()">Print / PDF</button>
@endsection

@section('content')
    <section class="panel report-filters no-print">
        <form method="GET" action="{{ route('admin.ad-sales.report') }}" class="report-filter-form">
            <div class="form-group">
                <label for="month">Month</label>
                <input class="form-control" type="month" id="month" name="month" value="{{ $month }}">
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
    </section>

    <div class="metrics-strip">
        <div><small>Views</small><strong>{{ number_format($totalViews) }}</strong><small>App {{ number_format($appViews) }} · Website {{ number_format($websiteViews) }}</small></div>
        <div><small>Clicks</small><strong>{{ number_format($totalClicks) }}</strong></div>
        <div><small>Click rate</small><strong>{{ $totalViews ? round($totalClicks / $totalViews * 100, 1) . '%' : '—' }}</strong></div>
        <div>
            <small>Money received</small>
            <strong>
                @forelse ($revenue as $currency => $total)
                    {{ number_format((float) $total) }} {{ $currency }}@if (!$loop->last) · @endif
                @empty
                    0
                @endforelse
            </strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading"><div><h2>{{ $from->format('F Y') }}</h2><p>Most viewed first. A view is one person seeing the ad on one day.</p></div></div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Ad</th><th>Type</th><th>Company</th><th>App views</th><th>Website views</th><th>Clicks</th><th>Click rate</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><strong>{{ $row['name'] }}</strong></td>
                            <td>{{ $row['type'] }}</td>
                            <td>{{ $row['company'] }}</td>
                            <td>{{ number_format($row['app_views']) }}</td>
                            <td>{{ number_format($row['website_views']) }}</td>
                            <td>{{ number_format($row['clicks']) }}</td>
                            <td>{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="dashboard-empty">No ad views in this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
