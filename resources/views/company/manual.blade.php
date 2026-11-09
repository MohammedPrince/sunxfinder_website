@extends('company.layouts.app')

@section('title', __('company/manual.title'))
@section('breadcrumb', __('company/manual.title'))
@section('eyebrow', __('company/manual.eyebrow'))
@section('page-title', __('company/manual.title'))
@section('page-description', __('company/manual.page_description'))

@section('page-actions')
    <button type="button" class="btn btn-light no-print" onclick="window.print()">{{ __('company/manual.print') }}</button>
@endsection

@section('content')
    @include('partials.manual', [
        // Follows the panel language (English / العربية in the top bar).
        't' => fn(string $key) => __('company/manual.' . $key),
        // Section => the page it explains. Advertising only when it is on for this company.
        'pages' => array_filter([
            'start' => 'company.dashboard',
            'profile' => 'company.profile',
            'products' => 'company.products.index',
            'quotes' => 'company.quote-requests.index',
            'package_offers' => 'company.offers.index',
            'projects' => 'company.projects.index',
            'invoices' => 'company.invoices.index',
            'customers' => 'company.customers.index',
            'notifications' => 'company.notifications.index',
            'advertising' => $hasAdvertising ? 'company.ads.index' : false,
            'ratings' => 'company.ratings.index',
            'reports' => 'company.reports.index',
            'help' => null,
        ], fn($route) => $route !== false),
    ])
@endsection
