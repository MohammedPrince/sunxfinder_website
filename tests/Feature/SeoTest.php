<?php

namespace Tests\Feature;

use App\Models\Setting;
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

        // Crawlable, so search engines can see the noindex on every page.
        $this->get('/robots.txt')->assertDontSee('Disallow')->assertDontSee('Sitemap:');
        $this->get('/')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_staging_hosts_are_never_indexed_even_without_the_env_setting(): void
    {
        foreach (['dev.sunxfinder.com', 'www.dev.sunxfinder.com'] as $host) {
            $this->get("https://{$host}/")
                ->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }

        $this->get('https://sunxfinder.com/')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_duplicate_addresses_of_the_live_site_redirect_to_one_https_address(): void
    {
        foreach ([
            'http://sunxfinder.com/' => 'https://sunxfinder.com/',
            'http://www.sunxfinder.com/terms?lang=ar' => 'https://sunxfinder.com/terms?lang=ar',
            'https://www.sunxfinder.com/terms' => 'https://sunxfinder.com/terms',
            'http://sunxfinder.com/robots.txt' => 'https://sunxfinder.com/robots.txt',
        ] as $from => $to) {
            $this->get($from)->assertStatus(301)->assertHeader('Location', $to);
        }

        $this->get('https://sunxfinder.com/terms')->assertOk();
        $this->get('http://localhost/terms')->assertOk();
        // Android and Apple do not follow redirects for these.
        $this->assertNotSame(301, $this->get('http://sunxfinder.com/.well-known/assetlinks.json')->status());
        // Form posts and the mobile app API are never redirected.
        $this->assertNotSame(301, $this->post('http://sunxfinder.com/contact')->status());
        $this->assertNotSame(301, $this->get('http://sunxfinder.com/api/v1/customer/unknown')->status());
    }

    public function test_index_php_addresses_redirect_to_the_clean_address(): void
    {
        $server = ['SCRIPT_NAME' => '/index.php', 'SCRIPT_FILENAME' => public_path('index.php')];

        $this->call('GET', 'https://sunxfinder.com/index.php/terms?lang=ar', server: $server)
            ->assertStatus(301)
            ->assertHeader('Location', 'https://sunxfinder.com/terms?lang=ar');
        $this->call('GET', 'https://sunxfinder.com/index.php', server: $server)
            ->assertStatus(301)
            ->assertHeader('Location', 'https://sunxfinder.com/');
        $this->call('GET', 'https://sunxfinder.com/terms', server: $server)->assertOk();
    }

    public function test_sitemap_urls_follow_the_requested_address(): void
    {
        $this->get('http://localhost/sitemap.xml')->assertOk();

        $this->get('https://sunxfinder.com/sitemap.xml')
            ->assertSee('<loc>https://sunxfinder.com/</loc>', false)
            ->assertDontSee('localhost');
    }

    public function test_files_for_crawlers_set_no_cookies(): void
    {
        foreach (['/robots.txt', '/sitemap.xml', '/googleafbc0ccb2ebc472f.html'] as $path) {
            $this->assertSame([], $this->get($path)->assertOk()->headers->getCookies(), $path);
        }
    }

    private function maintenance(bool $prelaunch): void
    {
        Setting::set('maintenance_enabled', '1');
        Setting::set('maintenance_prelaunch', $prelaunch ? '1' : '0');
        Cache::flush();
    }

    public function test_normal_maintenance_is_a_temporary_outage(): void
    {
        $this->maintenance(false);

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '3600')
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee('<meta name="description"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/terms');
    }

    public function test_coming_soon_home_page_can_be_indexed_before_launch(): void
    {
        config(['seo.google_verification' => 'test-code']);

        foreach (['new', 'classic'] as $design) {
            Setting::set('site_design', $design);
            $this->maintenance(true);

            $html = $this->get('/')->assertOk()->assertHeaderMissing('Retry-After')->getContent();
            $this->assertStringContainsString('<title>' . e(__('seo.home_title')) . '</title>', $html);
            $this->assertStringContainsString('<meta name="robots" content="index, follow', $html);
            $this->assertStringContainsString('<link rel="canonical" href="' . $this->home() . '">', $html);
            $this->assertStringContainsString('<meta name="google-site-verification" content="test-code">', $html);
            $this->assertStringNotContainsString('noindex', $html);
        }

        $this->get('/terms')->assertStatus(503)->assertSee('noindex');
        $this->get('/sitemap.xml')
            ->assertSee('<loc>' . $this->home() . '</loc>', false)
            ->assertDontSee('/terms');
    }

    public function test_google_verification_file_is_published(): void
    {
        $file = 'googleafbc0ccb2ebc472f.html';

        $this->assertSame("google-site-verification: {$file}", trim(file_get_contents(public_path($file))));

        // Answered by Laravel too (PROD is served from public_html), with no redirect, also in maintenance.
        $this->maintenance(false);
        foreach (['https', 'http'] as $scheme) {
            $response = $this->get("{$scheme}://sunxfinder.com/{$file}")->assertOk();
            $this->assertSame("google-site-verification: {$file}", trim($response->getContent()));
        }
        $this->get('https://sunxfinder.com/google0000000000000000.html')->assertNotFound();
    }
}
