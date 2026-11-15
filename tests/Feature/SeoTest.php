<?php

namespace Tests\Feature;

use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Search engine setup: meta tags, hreflang, structured data, robots.txt, sitemap.xml, noindex areas.
 * Run with: vendor/bin/phpunit -c phpunit.mysql.xml
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    protected function beforeRefreshingDatabase(): void
    {
        if (!str_ends_with((string) DB::connection()->getDatabaseName(), '_test')) {
            $this->markTestSkipped('Needs the MySQL test database: vendor/bin/phpunit -c phpunit.mysql.xml');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['seo.indexable' => true]);
    }

    private function company(string $name, string $slug, array $extra = []): SolarCompany
    {
        return SolarCompany::query()->forceCreate(array_merge([
            'name' => $name,
            'slug' => $slug,
            'username' => $slug,
            'email' => $slug . '@example.test',
            'phone' => '+249912345678',
            'city' => 'Khartoum',
            'license_number' => 'LIC-' . $slug,
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
            'description' => '<p>Solar systems for homes&nbsp;and farms in Khartoum.</p>',
            'services' => 'Solar panels, Batteries',
        ], $extra));
    }

    private function home(): string
    {
        return url('/') . '/';
    }

    /** @return array<int, array> every JSON-LD block on the page, decoded */
    private function jsonLd(string $html): array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);

        return array_map(function ($json) {
            $data = json_decode($json, true);
            $this->assertIsArray($data, 'Invalid JSON-LD: ' . $json);

            return $data;
        }, $matches[1]);
    }

    public function test_home_page_has_complete_search_tags_in_english(): void
    {
        $html = $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>' . e(__('seo.home_title')) . '</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="' . e(__('seo.home_description')) . '">', $html);
        $this->assertStringContainsString('<meta name="robots" content="index, follow', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . $this->home() . '">', $html);
        $this->assertStringContainsString('hreflang="en" href="' . $this->home() . '"', $html);
        $this->assertStringContainsString('hreflang="ar" href="' . $this->home() . '?lang=ar"', $html);
        $this->assertStringContainsString('hreflang="x-default" href="' . $this->home() . '"', $html);
        $this->assertStringContainsString('og-image.png', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);

        $types = collect($this->jsonLd($html))->flatMap(fn($block) => isset($block['@graph']) ? array_column($block['@graph'], '@type') : [$block['@type']]);
        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('FAQPage', $types);

        // Search engines never get the language pop-up over the page.
        $this->assertDoesNotMatchRegularExpression('/id="languageModal" role="presentation">/', $html);
    }

    public function test_arabic_page_points_to_its_own_canonical_url(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $html);
        $this->assertStringContainsString('<title>' . e(__('seo.home_title', [], 'ar')) . '</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . $this->home() . '?lang=ar">', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="ar_SD">', $html);
    }

    public function test_tracking_parameters_do_not_change_the_canonical_url(): void
    {
        $html = $this->get('/?utm_source=facebook&lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="' . $this->home() . '">', $html);
    }

    public function test_company_profile_has_its_own_title_description_and_business_data(): void
    {
        $this->company('Red Sea Solar', 'red-sea-solar');

        $html = $this->get('/companies/red-sea-solar')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Red Sea Solar: Solar Company in Khartoum | SunXFinder</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Solar systems for homes and farms in Khartoum.">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . url('/companies/red-sea-solar') . '">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="profile">', $html);

        $blocks = collect($this->jsonLd($html));
        $business = $blocks->firstWhere('@type', 'HomeAndConstructionBusiness');
        $this->assertSame('Red Sea Solar', $business['name']);
        $this->assertSame('Khartoum', $business['address']['addressLocality']);
        $this->assertSame('+249912345678', $business['telephone']);
        $this->assertSame(['Solar panels', 'Batteries'], array_map(fn($offer) => $offer['itemOffered']['name'], $business['makesOffer']));
        $this->assertArrayNotHasKey('aggregateRating', $business, 'No rating until a customer rates the company.');
        $this->assertNotNull($blocks->firstWhere('@type', 'BreadcrumbList'));
    }

    public function test_company_hidden_phone_is_not_published_in_structured_data(): void
    {
        $this->company('Nile Power', 'nile-power', ['show_phone_in_app' => false, 'description' => null]);

        $html = $this->get('/companies/nile-power')->assertOk()->getContent();
        $business = collect($this->jsonLd($html))->firstWhere('@type', 'HomeAndConstructionBusiness');

        $this->assertArrayNotHasKey('telephone', $business);
        // No description of its own: a generated one is used.
        $this->assertStringContainsString('Nile Power is a verified solar energy company in Khartoum.', $html);
    }

    public function test_robots_txt_lists_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /go/')
            ->assertSee('Sitemap: ' . url('/sitemap.xml'));
    }

    public function test_sitemap_lists_public_pages_and_visible_companies_in_both_languages(): void
    {
        $this->company('Red Sea Solar', 'red-sea-solar');
        $this->company('Hidden Solar', 'hidden-solar', ['status' => 'pending']);
        $this->company('Inactive Solar', 'inactive-solar', ['is_active' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap.xml is not valid XML');
        $this->assertStringContainsString('<loc>' . $this->home() . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . $this->home() . '?lang=ar</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/companies/red-sea-solar') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/companies/red-sea-solar') . '?lang=ar</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/create-company-account') . '</loc>', $xml);
        $this->assertStringNotContainsString('hidden-solar', $xml);
        $this->assertStringNotContainsString('inactive-solar', $xml);
    }

    public function test_panels_and_app_handoff_pages_are_not_indexed(): void
    {
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/company/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');

        $this->company('Red Sea Solar', 'red-sea-solar');
        $this->get('/companies/red-sea-solar')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_staging_server_is_kept_out_of_search_results(): void
    {
        config(['seo.indexable' => false]);

        $this->get('/robots.txt')->assertSee("Disallow: /\n", false)->assertDontSee('Sitemap:');
        $this->get('/')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('rel="canonical"', false);
    }
}
