@extends('company.layouts.app')

@section('title', __('company/messages.ads.monthly_report'))
@section('breadcrumb', __('company/messages.ads.title'))
@section('eyebrow', __('company/messages.ads.eyebrow'))
@section('page-title', __('company/messages.ads.monthly_report'))
@section('page-description', __('company/messages.ads.report_description'))

@section('page-actions')
    <a href="{{ route('company.ads.index') }}" class="btn btn-light no-print">{{ __('company/messages.ads.back') }}</a>
    <button type="button" class="btn btn-light no-print" onclick="window.print()">{{ __('company/messages.reports.print') }}</button>
@endsection

@section('content')
    <section class="panel report-filters no-print">
        <form method="GET" action="{{ route('company.ads.report') }}" class="report-filter-form">
            <div class="form-group">
                <label for="month">{{ __('company/messages.ads.month') }}</label>
                <input class="form-control" type="month" id="month" name="month" value="{{ $month }}">
            </div>
            <button type="submit" class="btn btn-primary">{{ __('company/messages.reports.apply') }}</button>
        </form>
    </section>

    <div class="metrics-strip">
        <div>
            <small>{{ __('company/messages.ads.views') }}</small>
            <strong>{{ number_format($totalViews) }}</strong>
            <small>{{ __('company/messages.ads.views_split', ['app' => number_format($appViews), 'website' => number_format($websiteViews)]) }}</small>
        </div>
        <div><small>{{ __('company/messages.ads.clicks') }}</small><strong>{{ number_format($totalClicks) }}</strong></div>
        <div><small>{{ __('company/messages.ads.click_rate') }}</small><strong>{{ $totalRate !== null ? $totalRate . '%' : '—' }}</strong></div>
        <div><small>{{ __('company/messages.ads.sends_month') }}</small><strong>{{ number_format($sponsoredSends) }}</strong></div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>{{ $from->translatedFormat('F Y') }}</h2>
                <p>{{ __('company/messages.ads.report_sub') }}</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.ads.ad') }}</th>
                        <th>{{ __('company/messages.ads.app_views') }}</th>
                        <th>{{ __('company/messages.ads.website_views') }}</th>
                        <th>{{ __('company/messages.ads.clicks') }}</th>
                        <th>{{ __('company/messages.ads.click_rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ __('company/messages.ads.products.' . $row['type']) }}</strong>
                                @if ($row['name'])
                                    <small class="cell-note">{{ $row['name'] }}</small>
                                @endif
                            </td>
                            <td>{{ number_format($row['app_views']) }}</td>
                            <td>{{ number_format($row['website_views']) }}</td>
                            <td>{{ number_format($row['clicks']) }}</td>
                            <td>{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="dashboard-empty">{{ __('company/messages.ads.report_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
