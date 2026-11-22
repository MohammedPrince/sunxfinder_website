{{-- FAQPage structured data from the home page FAQ: [[question key, [answer keys...]], ...] --}}
@php
    $faqData = [
        '@type' => 'FAQPage',
        'inLanguage' => app()->getLocale(),
        'mainEntity' => collect($faqs)->map(fn($faq) => [
            '@type' => 'Question',
            'name' => __($faq[0]),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => collect($faq[1])->map(fn($answer) => __($answer))->implode(' '),
            ],
        ])->values()->all(),
    ];
@endphp
<script type="application/ld+json">{!! \App\Support\Seo::jsonLd($faqData) !!}</script>
