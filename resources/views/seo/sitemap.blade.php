{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}{{-- split: never read as a PHP open tag --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
@foreach (['en', 'ar'] as $locale)
    <url>
        <loc>{{ $page['alternates'][$locale] }}</loc>
@foreach ($page['alternates'] as $hreflang => $href)
        <xhtml:link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}"/>
@endforeach
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@endforeach
</urlset>
