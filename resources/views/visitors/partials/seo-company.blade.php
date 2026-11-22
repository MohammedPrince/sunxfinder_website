{{--
    Search tags for a public company profile (both website designs).
    Uses the variables WebsiteController::showCompany passes to the profile view.
--}}
@php
    $seoCity = $companyCity ?: __('messages.Sudan');
    $seoDescription = \App\Support\Seo::description($companyDescription)
        ?: __($companyVerified ? 'seo.company_description_verified' : 'seo.company_description', ['name' => $companyName, 'city' => $seoCity]);
    $profileUrl = \App\Support\Seo::canonical();
    // Same logo URL the page shows (handles old uploads that stored a full URL).
    $companyLogo = $companyLogo ? app(\App\Helpers\Helper::class)->publicUrl($company->logo_path) : null;

    $business = array_filter([
        '@type' => 'HomeAndConstructionBusiness',
        '@id' => route('company-profile.show', $company->slug) . '#business',
        'name' => $companyName,
        'url' => $profileUrl,
        'description' => $seoDescription,
        'logo' => $companyLogo,
        'image' => $companyLogo ?: \App\Support\Seo::imageUrl(),
        'telephone' => $company->showsPhoneInApp() ? ($companyPhone ?: null) : null,
        'email' => $company->showsEmailInApp() ? ($companyEmail ?: null) : null,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $company->city ?: null,
            'addressCountry' => 'SD',
        ],
        'areaServed' => ['@type' => 'Country', 'name' => 'Sudan'],
        'knowsAbout' => ['Solar energy', 'Solar panels', 'Solar installation', 'Batteries', 'Inverters'],
        'makesOffer' => collect($companyServices ?? [])->take(10)->map(fn($service) => [
            '@type' => 'Offer',
            'itemOffered' => ['@type' => 'Service', 'name' => $service],
        ])->values()->all() ?: null,
        'aggregateRating' => $companyRating && $companyReviews > 0 ? [
            '@type' => 'AggregateRating',
            'ratingValue' => $companyRating,
            'reviewCount' => (int) $companyReviews,
            'bestRating' => 5,
            'worstRating' => 1,
        ] : null,
        'review' => collect($companyReviewsList ?? [])->take(5)->map(fn($review) => array_filter([
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $review['customer'] ?: 'SunXFinder customer'],
            'datePublished' => $review['date'] ?? null,
            'reviewBody' => $review['comment'] ?: null,
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (int) $review['rating'], 'bestRating' => 5, 'worstRating' => 1],
        ]))->values()->all() ?: null,
        'sameAs' => array_values(array_filter([$companyWebsite ?: null])) ?: null,
    ], fn($value) => $value !== null && $value !== []);

    $business['address'] = array_filter($business['address']);

    $crumbs = \App\Support\Seo::breadcrumbs([
        __('seo.home') => \App\Support\Seo::localizedUrl(url('/'), app()->getLocale()),
        $companyName => $profileUrl,
    ]);
@endphp

@section('title', __('seo.company_title', ['name' => $companyName, 'city' => $seoCity]))
@section('meta_description', $seoDescription)
@section('og_type', 'profile')
@if ($companyLogo)
    @section('og_image', $companyLogo)
@endif

@push('structured-data')
    <script type="application/ld+json">{!! \App\Support\Seo::jsonLd($business) !!}</script>
    <script type="application/ld+json">{!! \App\Support\Seo::jsonLd($crumbs) !!}</script>
@endpush
