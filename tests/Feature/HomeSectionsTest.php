<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppSlider;
use App\Models\Customer;
use App\Models\HomeSection;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomeSectionsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Admin $admin;

    /** See CompanyOfferInvoiceTest: run with vendor/bin/phpunit -c phpunit.mysql.xml */
    protected function beforeRefreshingDatabase(): void
    {
        if (!str_ends_with((string) DB::connection()->getDatabaseName(), '_test')) {
            $this->markTestSkipped('Needs the MySQL test database: vendor/bin/phpunit -c phpunit.mysql.xml');
        }

        // RefreshDatabase rolls each test back in a transaction, which MyISAM ignores.
        config(['database.connections.mysql.engine' => 'InnoDB']);
        DB::purge('mysql');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->customer = Customer::query()->forceCreate([
            'username' => 'mohamed',
            'password' => Hash::make('secret123'),
            'full_name' => 'Mohamed Ahmed',
            'phone' => '+249900000001',
            'email' => 'customer@example.test',
            'city' => 'Khartoum',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->admin = Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    }

    private function makeSlide(string $name, array $overrides = []): AppSlider
    {
        return AppSlider::query()->forceCreate(array_merge([
            'name' => $name,
            'image_path' => 'main/images/sliders/' . $name . '.webp',
            'platform' => 'app',
            'interval_seconds' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ], $overrides));
    }

    private function saveFeatured(array $fields)
    {
        return $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.home-sections.update', 'featured_companies'), $fields);
    }

    public function test_home_sections_returns_the_seeded_row_with_only_whitelisted_fields(): void
    {
        Sanctum::actingAs($this->customer);

        $sections = $this->getJson('/api/v1/customer/home-sections')
            ->assertOk()
            ->assertJsonPath('message', 'Home sections loaded.')
            ->json('data.home_sections');

        $this->assertSame([[
            'key' => 'featured_companies',
            'is_visible' => true,
            'show_companies' => true,
            'title_en' => null,
            'title_ar' => null,
        ]], $sections);
    }

    public function test_admin_changes_reach_home_sections_and_main_data_right_away(): void
    {
        Sanctum::actingAs($this->customer);

        // Warm the cache with the old values first.
        $this->getJson('/api/v1/customer/home-sections')->assertJsonPath('data.home_sections.0.is_visible', true);

        $this->saveFeatured([
            'is_visible' => '1',
            'show_companies' => '0',
            'title_en' => ' Our Partners ',
            'title_ar' => 'شركاؤنا',
        ])->assertRedirect()->assertSessionHas('success');

        $expected = [
            'key' => 'featured_companies',
            'is_visible' => true,
            'show_companies' => false,
            'title_en' => 'Our Partners',
            'title_ar' => 'شركاؤنا',
        ];

        Sanctum::actingAs($this->customer);
        $this->assertSame([$expected], $this->getJson('/api/v1/customer/home-sections')->json('data.home_sections'));
        $this->assertSame([$expected], $this->postJson('/api/v1/customer/main-data')->assertOk()->json('data.home_sections'));

        $this->saveFeatured(['is_visible' => '0', 'show_companies' => '1', 'title_en' => '', 'title_ar' => '']);

        Sanctum::actingAs($this->customer);
        $this->getJson('/api/v1/customer/home-sections')
            ->assertJsonPath('data.home_sections.0.is_visible', false)
            ->assertJsonPath('data.home_sections.0.show_companies', true)
            ->assertJsonPath('data.home_sections.0.title_en', null);
    }

    public function test_sliders_and_main_data_include_placement_and_old_slides_read_home_top(): void
    {
        // An old slide: created without a placement, so it gets the column default.
        $old = $this->makeSlide('old');
        $featured = $this->makeSlide('featured', ['placement' => 'home_featured', 'sort_order' => 2]);

        $this->assertSame('home_top', $old->fresh()->placement);

        Sanctum::actingAs($this->customer);

        $placements = collect($this->getJson('/api/v1/customer/sliders')->assertOk()->json('data.sliders'))
            ->pluck('placement', 'id')->all();
        $this->assertSame([$old->id => 'home_top', $featured->id => 'home_featured'], $placements);

        $placements = collect($this->postJson('/api/v1/customer/main-data')->assertOk()->json('data.sliders'))
            ->pluck('placement', 'id')->all();
        $this->assertSame([$old->id => 'home_top', $featured->id => 'home_featured'], $placements);
    }

    public function test_sliders_can_be_filtered_by_placement(): void
    {
        $this->makeSlide('top');
        $featured = $this->makeSlide('featured', ['placement' => 'home_featured']);
        $this->makeSlide('bottom', ['placement' => 'home_bottom']);

        Sanctum::actingAs($this->customer);

        $ids = collect($this->getJson('/api/v1/customer/sliders?placement=home_featured')->assertOk()->json('data.sliders'))
            ->pluck('id')->all();
        $this->assertSame([$featured->id], $ids);

        $this->getJson('/api/v1/customer/sliders?placement=bogus')
            ->assertStatus(422)
            ->assertJsonValidationErrors('placement');
    }

    public function test_each_placement_gets_its_own_ten_slides(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeSlide("top{$i}", ['sort_order' => $i]);
        }
        $featured = $this->makeSlide('featured', ['placement' => 'home_featured', 'sort_order' => 99]);

        Sanctum::actingAs($this->customer);

        $sliders = collect($this->getJson('/api/v1/customer/sliders')->assertOk()->json('data.sliders'));

        $this->assertCount(10, $sliders->where('placement', 'home_top'));
        $this->assertSame([$featured->id], $sliders->where('placement', 'home_featured')->pluck('id')->values()->all());
    }

    public function test_inactive_slides_never_appear_whatever_their_placement(): void
    {
        $live = $this->makeSlide('live', ['placement' => 'home_bottom']);
        foreach (array_keys(AppSlider::PLACEMENTS) as $placement) {
            $this->makeSlide('hidden-' . $placement, ['placement' => $placement, 'is_active' => false]);
        }

        Sanctum::actingAs($this->customer);

        $this->assertSame([$live->id], collect($this->getJson('/api/v1/customer/sliders')->json('data.sliders'))->pluck('id')->all());
        $this->assertSame([], $this->getJson('/api/v1/customer/sliders?placement=home_featured')->json('data.sliders'));
    }

    public function test_slider_form_rejects_an_unknown_placement(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.app-sliders.create'))
            ->post(route('admin.app-sliders.store'), [
                'name' => 'Offer',
                'platform' => 'app',
                'placement' => 'home_sidebar',
                'interval_seconds' => 5,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.app-sliders.create'))
            ->assertSessionHasErrors('placement');

        $this->assertSame(0, AppSlider::count());
    }

    public function test_non_admins_cannot_reach_the_home_section_settings(): void
    {
        $company = SolarCompany::query()->forceCreate([
            'name' => 'Red Sea Solar',
            'slug' => 'redsea',
            'username' => 'redsea',
            'email' => 'redsea@example.test',
            'phone' => '+249900000002',
            'city' => 'Khartoum',
            'license_number' => 'LIC-redsea',
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $url = route('admin.settings.home-sections.update', 'featured_companies');

        $this->put($url, ['is_visible' => '0'])->assertRedirect(route('admin.login'));
        $this->actingAs($company, 'company')->put($url, ['is_visible' => '0'])->assertRedirect(route('admin.login'));
        $this->actingAs($this->customer)->get(route('admin.settings.index'))->assertRedirect(route('admin.login'));

        $this->assertTrue(HomeSection::query()->where('key', 'featured_companies')->value('is_visible'));
    }

    public function test_admin_sees_the_app_home_card_on_the_settings_page(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('App Home')
            ->assertSee('Show companies in this section');
    }
}
