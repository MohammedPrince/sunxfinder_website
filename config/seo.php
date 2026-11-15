<?php

return [
    /*
    | Search engines may index the public website. Set SEO_INDEXABLE=false on test/staging
    | servers (e.g. dev.sunxfinder.com): every page then says noindex and robots.txt blocks all.
    */
    'indexable' => (bool) env('SEO_INDEXABLE', true),

    'site_name' => 'SunXFinder',

    // 1200x630 image used when a page is shared (WhatsApp, Facebook, X, LinkedIn).
    'image' => 'visitors/images/og-image.png',
    'image_width' => 1200,
    'image_height' => 630,

    // Public contact shown in the structured data (Google knowledge panel).
    'email' => env('SEO_CONTACT_EMAIL', 'info@sunxfinder.com'),

    // Official social profiles (comma separated URLs), linked to the brand in search results.
    'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_SAME_AS', ''))))),

    // Search Console / Bing Webmaster verification codes (the content="" value only).
    'google_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_verification' => env('BING_SITE_VERIFICATION'),
];
