{{--
    Search engine and link-preview tags for every public page (visitors layout).
    A page can set: @section('title'), @section('meta_description'), @section('meta_robots'),
    @section('og_type'), @section('og_image') and @push('structured-data') for its own JSON-LD.
--}}
@use('App\Support\Seo')
@php
    $seoLocale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    // Short @section values arrive HTML-escaped: decode once, the tags below escape again.
    $seoTitle = trim(html_entity_decode($__env->yieldContent('title', __('seo.home_title')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $seoDescription = Seo::description($__env->yieldContent('meta_description', __('seo.home_description')));
    $seoImage = trim(html_entity_decode($__env->yieldContent('og_image'), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: Seo::imageUrl();
    $seoCustomImage = $seoImage !== Seo::imageUrl();
    $seoRobots = Seo::indexable()
        ? trim($__env->yieldContent('meta_robots', 'index, follow, max-image-preview:large, max-snippet:-1'))
        : 'noindex, nofollow';
    $seoNoindex = str_contains($seoRobots, 'noindex');
    $seoCanonical = Seo::canonical($seoLocale);
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
@unless ($seoNoindex)
    <link rel="canonical" href="{{ $seoCanonical }}">
    @foreach (Seo::alternates() as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach
@endunless
@if (config('seo.google_verification'))
    <meta name="google-site-verification" content="{{ config('seo.google_verification') }}">
@endif
@if (config('seo.bing_verification'))
    <meta name="msvalidate.01" content="{{ config('seo.bing_verification') }}">
@endif

{{-- Link previews (WhatsApp, Facebook, LinkedIn, X) --}}
<meta property="og:site_name" content="{{ config('seo.site_name') }}">
<meta property="og:type" content="{{ trim($__env->yieldContent('og_type', 'website')) }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:locale" content="{{ $seoLocale === 'ar' ? 'ar_SD' : 'en_US' }}">
<meta property="og:locale:alternate" content="{{ $seoLocale === 'ar' ? 'en_US' : 'ar_SD' }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ $seoTitle }}">
@unless ($seoCustomImage)
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="{{ config('seo.image_width') }}">
    <meta property="og:image:height" content="{{ config('seo.image_height') }}">
@endunless
<meta name="twitter:card" content="{{ $seoCustomImage ? 'summary' : 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

{{-- The brand and the site, on every page --}}
<script type="application/ld+json">{!! Seo::jsonLd(['@graph' => [Seo::organization(), Seo::website()]]) !!}</script>
@stack('structured-data')
