<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * URLs and helpers for search engines. The site has one URL per language:
 * English is the clean URL (also x-default), Arabic is the same URL with ?lang=ar.
 */
class Seo
{
    public const LOCALES = ['en', 'ar'];

    public static function indexable(): bool
    {
        return (bool) config('seo.indexable', true);
    }

    /** The URL of a page in one language (no other query string). */
    public static function localizedUrl(string $url, string $locale): string
    {
        $url = strtok($url, '?');

        // The home page is "https://sunxfinder.com/", never "https://sunxfinder.com?lang=ar".
        if (parse_url($url, PHP_URL_PATH) === null) {
            $url .= '/';
        }

        return $locale === 'ar' ? $url . '?lang=ar' : $url;
    }

    /** Canonical URL of the current page in the current language. */
    public static function canonical(?string $locale = null): string
    {
        return self::localizedUrl(url()->current(), $locale ?? app()->getLocale());
    }

    /** hreflang => URL for the current page. */
    public static function alternates(?string $url = null): array
    {
        $url ??= url()->current();

        return [
            'en' => self::localizedUrl($url, 'en'),
            'ar' => self::localizedUrl($url, 'ar'),
            'x-default' => self::localizedUrl($url, 'en'),
        ];
    }

    /** One-line plain text of at most $limit characters, cut on a word. */
    public static function description(?string $text, int $limit = 158): string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', $text));

        return Str::limit($text, $limit, '…', true);
    }

    /** Search engine and link-preview bots (they never see the language pop-up). */
    public static function isCrawler(?string $userAgent): bool
    {
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|bingpreview|mediapartners|lighthouse|chrome-lighthouse|facebookexternalhit|whatsapp|telegram|twitterbot|linkedin|embedly|pinterest|duckduck|yandex|baidu|applebot|petalbot|semrush|ahrefs/i',
            (string) $userAgent
        );
    }

    /**
     * JSON for a <script type="application/ld+json"> block (safe inside HTML). Adds the schema.org
     * context, so Blade files never write the key themselves (Blade reads it as a directive).
     */
    public static function jsonLd(array $data): string
    {
        return json_encode(
            ['@' . 'context' => 'https://schema.org'] + $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT
        );
    }

    public static function imageUrl(): string
    {
        return asset(config('seo.image'));
    }

    /** Organization: the brand behind the site, shared by every page. */
    public static function organization(): array
    {
        $phone = WhatsApp::number(config('services.whatsapp.phone_number'));

        return array_filter([
            '@type' => 'Organization',
            '@id' => self::localizedUrl(url('/'), 'en') . '#organization',
            'name' => config('seo.site_name'),
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('visitors/images/logo-512.png'),
                'width' => 512,
                'height' => 512,
            ],
            'image' => self::imageUrl(),
            'email' => config('seo.email'),
            'areaServed' => ['@type' => 'Country', 'name' => 'Sudan'],
            'contactPoint' => array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => config('seo.email'),
                'telephone' => $phone ? '+' . $phone : null,
                'availableLanguage' => ['English', 'Arabic'],
                'areaServed' => 'SD',
            ]),
            'sameAs' => config('seo.same_as') ?: null,
        ]);
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::localizedUrl(url('/'), 'en') . '#website',
            'name' => config('seo.site_name'),
            'url' => url('/'),
            'inLanguage' => ['en', 'ar'],
            'publisher' => ['@id' => self::localizedUrl(url('/'), 'en') . '#organization'],
        ];
    }

    /** BreadcrumbList from [name => url, ...]. */
    public static function breadcrumbs(array $items): array
    {
        $position = 0;

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->map(fn($url, $name) => [
                '@type' => 'ListItem',
                'position' => ++$position,
                'name' => $name,
                'item' => $url,
            ])->values()->all(),
        ];
    }
}
