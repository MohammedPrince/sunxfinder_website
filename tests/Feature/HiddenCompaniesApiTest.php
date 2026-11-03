<?php

namespace Tests\Feature;

use App\Models\CompanyCarousel;
use App\Models\Customer;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** The customer API only ever shows companies that are active, verified and approved. */
class HiddenCompaniesApiTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private SolarCompany $visible;

    /** @var array<string, SolarCompany> */
    private array $hidden = [];

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

        $this->visible = $this->makeCompany('visible');
        $this->hidden = [
            'pending' => $this->makeCompany('pending', ['status' => 'pending']),
            'rejected' => $this->makeCompany('rejected', ['status' => 'rejected']),
            'unverified' => $this->makeCompany('unverified', ['is_verified' => false]),
            'inactive' => $this->makeCompany('inactive', ['is_active' => false]),
            'off_app' => $this->makeCompany('offapp', ['on_app' => false]),
        ];

        Sanctum::actingAs($this->customer);
    }

    private function makeCompany(string $slug, array $overrides = []): SolarCompany
    {
        static $n = 10;
        $n++;

        return SolarCompany::query()->forceCreate(array_merge([
            'name' => ucfirst($slug) . ' Solar',
            'slug' => $slug,
            'username' => $slug,
            'email' => $slug . '@example.test',
            'phone' => '+2499000000' . $n,
            'city' => 'Khartoum',
            'license_number' => 'LIC-' . $slug,
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
        ], $overrides));
    }

    public function test_companies_lists_only_visible_companies(): void
    {
        $ids = collect($this->getJson('/api/v1/customer/companies?per_page=50')->assertOk()->json('data.companies'))
            ->pluck('id')->all();

        $this->assertSame([$this->visible->id], $ids);
        $this->assertSame(1, $this->getJson('/api/v1/customer/companies')->json('meta.total'));
    }

    public function test_company_detail_is_404_for_a_hidden_company(): void
    {
        $this->getJson('/api/v1/customer/companies/' . $this->visible->id)->assertOk();
        $this->getJson('/api/v1/customer/companies/slug/visible')->assertOk();

        foreach ($this->hidden as $company) {
            $this->getJson('/api/v1/customer/companies/' . $company->id)
                ->assertNotFound()
                ->assertJsonPath('error', 'Company not found.');
            $this->getJson('/api/v1/customer/companies/slug/' . $company->slug)->assertNotFound();
        }
    }

    public function test_carousels_skip_entries_whose_company_is_hidden(): void
    {
        $entry = function (SolarCompany $company, int $order): CompanyCarousel {
            return CompanyCarousel::query()->forceCreate([
                'company_id' => $company->id,
                'platform' => 'app',
                'interval_seconds' => 5,
                'sort_order' => $order,
                'is_active' => true,
            ]);
        };

        $live = $entry($this->visible, 5);
        $entry($this->hidden['pending'], 1);
        $entry($this->hidden['unverified'], 2);
        $entry($this->hidden['inactive'], 3);

        $ids = collect($this->getJson('/api/v1/customer/carousels')->assertOk()->json('data.carousels'))
            ->pluck('id')->all();

        $this->assertSame([$live->id], $ids);
    }

    public function test_carousel_eye_toggle_sets_on_app_and_hides_the_company_from_the_app(): void
    {
        $admin = \App\Models\Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
        $entry = CompanyCarousel::query()->forceCreate([
            'company_id' => $this->visible->id,
            'platform' => 'app',
            'interval_seconds' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $toggle = fn() => $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patch(route('admin.company-carousel.toggle', $entry->id))
            ->assertRedirect();

        $toggle();
        $this->assertFalse($entry->fresh()->is_active);
        $this->assertFalse($this->visible->fresh()->on_app);

        Sanctum::actingAs($this->customer);
        $this->assertSame([], $this->getJson('/api/v1/customer/companies')->json('data.companies'));
        $this->getJson('/api/v1/customer/companies/' . $this->visible->id)->assertNotFound();

        $toggle();
        $this->assertTrue($this->visible->fresh()->on_app);

        Sanctum::actingAs($this->customer);
        $this->getJson('/api/v1/customer/companies')
            ->assertJsonPath('data.companies.0.id', $this->visible->id)
            ->assertJsonPath('data.companies.0.on_app', true);
    }

    public function test_main_data_returns_only_visible_companies(): void
    {
        $data = $this->postJson('/api/v1/customer/main-data')->assertOk()->json('data');

        $this->assertSame([$this->visible->id], collect($data['companies'])->pluck('id')->all());
        $this->assertSame(1, $data['meta']['companies_total']);
    }
}
