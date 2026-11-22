<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\SolarCompany;
use App\Support\Seo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/** robots.txt and sitemap.xml for search engines. */
class SeoController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'seo:sitemap';

    public function robots()
    {
        $lines = Seo::indexable()
            ? [
                'User-agent: *',
                // Ad click counters, app hand-off pages, the APK and the API are not pages.
                'Disallow: /go/',
                'Disallow: /open/',
                'Disallow: /download/',
                'Disallow: /api/',
                // Admin and company panels answer with "noindex", so search engines may crawl them to see it.
                'Allow: /',
                '',
                'Sitemap: ' . route('seo.sitemap'),
            ]
            : [
                // Test / staging server: every page answers "noindex". Crawling stays allowed, because
                // a blocked crawler never sees the noindex and can keep the URL in its results.
                'User-agent: *',
                'Allow: /',
            ];

        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function sitemap()
    {
        // Only the home page is open before launch; the other pages answer 503.
        $homeOnly = Setting::isPrelaunch();
        // The URLs inside follow the requested address, so each address has its own copy.
        $key = self::SITEMAP_CACHE_KEY . ':' . md5(url('/') . ($homeOnly ? '|home' : ''));

        $xml = Cache::remember($key, 3600, fn() => view('seo.sitemap', [
            'pages' => array_slice($this->sitemapPages(), 0, $homeOnly ? 1 : null),
        ])->render());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** [url (English), lastmod, changefreq, priority] for every public page, each also in Arabic. */
    private function sitemapPages(): array
    {
        $legalUpdated = $this->legalUpdatedAt();

        $pages = [
            [route('home'), $this->latestCompanyUpdate(), 'daily', '1.0'],
            [route('company.register'), null, 'monthly', '0.8'],
            [route('customer.register'), null, 'monthly', '0.7'],
            [route('terms'), $legalUpdated, 'yearly', '0.3'],
            [route('privacy-policy'), $legalUpdated, 'yearly', '0.3'],
            [route('delete-account'), null, 'yearly', '0.2'],
        ];

        // Public company profiles: the same companies WebsiteController::showCompany shows.
        SolarCompany::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByDesc('is_verified')
            ->orderBy('id')
            ->get(['slug', 'updated_at', 'is_verified'])
            ->each(function (SolarCompany $company) use (&$pages) {
                $pages[] = [
                    route('company-profile.show', $company->slug),
                    $company->updated_at,
                    'weekly',
                    $company->is_verified ? '0.9' : '0.7',
                ];
            });

        return array_map(fn($page) => [
            'url' => $page[0],
            'alternates' => Seo::alternates($page[0]),
            'lastmod' => $page[1] ? Carbon::parse($page[1])->toAtomString() : null,
            'changefreq' => $page[2],
            'priority' => $page[3],
        ], $pages);
    }

    private function latestCompanyUpdate(): ?Carbon
    {
        $latest = SolarCompany::query()->where('status', 'approved')->where('is_active', true)->max('updated_at');

        return $latest ? Carbon::parse($latest) : null;
    }

    private function legalUpdatedAt(): ?Carbon
    {
        try {
            $latest = \App\Models\LegalSection::query()->max('updated_at');
        } catch (\Throwable) {
            return null;
        }

        return $latest ? Carbon::parse($latest) : null;
    }
}
