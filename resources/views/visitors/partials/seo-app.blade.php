{{-- The SunXFinder Android app, when the Google Play link is published (home page). --}}
@if (!empty($playStoreUrl))
    @php
        $appData = [
            '@type' => 'MobileApplication',
            'name' => 'SunXFinder',
            'operatingSystem' => 'Android',
            'applicationCategory' => 'UtilitiesApplication',
            'description' => __('seo.home_description'),
            'url' => $playStoreUrl,
            'image' => asset('visitors/images/logo-512.png'),
            'inLanguage' => ['en', 'ar'],
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
            'publisher' => ['@id' => \App\Support\Seo::localizedUrl(url('/'), 'en') . '#organization'],
        ];
    @endphp
    <script type="application/ld+json">{!! \App\Support\Seo::jsonLd($appData) !!}</script>
@endif
