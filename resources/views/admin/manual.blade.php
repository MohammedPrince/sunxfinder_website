@extends('admin.layouts.app')

@php
    // The admin panel is English; the manual itself can be read in English or Arabic.
    $t = fn(string $key) => __('admin/manual.' . $key, [], $lang);
@endphp

@section('title', $t('title'))
@section('breadcrumb', $t('title'))
@section('eyebrow', $t('eyebrow'))
@section('page-title', $t('title'))
@section('page-description', $t('page_description'))

@section('page-actions')
    <nav class="manual-lang no-print" aria-label="Manual language">
        <a href="{{ route('admin.manual', ['lang' => 'en']) }}" lang="en" @class(['is-active' => $lang === 'en'])>English</a>
        <a href="{{ route('admin.manual', ['lang' => 'ar']) }}" lang="ar" @class(['is-active' => $lang === 'ar'])>العربية</a>
    </nav>
    <button type="button" class="btn btn-light no-print" onclick="window.print()">{{ $t('print') }}</button>
@endsection

@section('content')
    @include('partials.manual', [
        't' => $t,
        'dir' => $lang === 'ar' ? 'rtl' : 'ltr',
        // Section => the page it explains, in display order.
        'pages' => [
            'start' => 'admin.dashboard.page',
            'companies' => 'admin.companies.index',
            'customers' => 'admin.customers.index',
            'catalog' => 'admin.products.index',
            'calculator' => 'admin.calculator-groups.index',
            'app_home' => 'admin.app-sliders.index',
            'ad_sales' => 'admin.ad-sales.index',
            'app_versions' => 'admin.app-versions.index',
            'quotes' => 'admin.quote-requests.index',
            'projects' => 'admin.projects.index',
            'complaints' => 'admin.complaints.index',
            'notifications' => 'admin.notifications.index',
            'ratings' => 'admin.ratings.index',
            'reports' => 'admin.reports.index',
            'send_email' => 'admin.send-email.index',
            'settings' => 'admin.settings.index',
            'legal' => 'admin.legal.index',
            'system' => 'admin.queue.index',
            'server' => null,
        ],
    ])
@endsection
