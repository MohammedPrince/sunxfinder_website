<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** "Show on company profile" on the product form controls the public company page. */
class CompanyProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private SolarCompany $company;
    private int $supplierId;

    /** MySQL-only migrations: vendor/bin/phpunit -c phpunit.mysql.xml */
    protected function beforeRefreshingDatabase(): void
    {
        if (!str_ends_with((string) DB::connection()->getDatabaseName(), '_test')) {
            $this->markTestSkipped('Needs the MySQL test database: vendor/bin/phpunit -c phpunit.mysql.xml');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->company = SolarCompany::query()->forceCreate([
            'name' => 'RE Energy Solar Co',
            'slug' => 're-energy',
            'username' => 're-energy',
            'email' => 're@example.test',
            'phone' => '+249900000003',
            'city' => 'Port Sudan',
            'license_number' => 'LIC-re',
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $this->supplierId = DB::table('tbl_suppliers')->insertGetId([
            'name' => 'Deye',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The company panel reads the company from the session, as the login sets it.
        $this->actingAs($this->company, 'company')
            ->withSession(['company_id' => $this->company->id]);
    }

    /** What the browser sends: the hidden 0, then the checkbox's 1 only when it is ticked. */
    private function productForm(string $name, bool $shown): array
    {
        return [
            'supplier_id' => $this->supplierId,
            'name' => $name,
            'type' => 'inverter',
            'is_active' => $shown ? '1' : '0',
        ];
    }

    public function test_a_product_created_hidden_stays_off_the_public_profile(): void
    {
        $this->post(route('company.products.store'), $this->productForm('Hidden Deye 5kW', false))
            ->assertRedirect();
        $this->post(route('company.products.store'), $this->productForm('Shown Deye 8kW', true))
            ->assertRedirect();

        $this->assertFalse((bool) Product::query()->where('name', 'Hidden Deye 5kW')->value('is_active'));
        $this->assertTrue((bool) Product::query()->where('name', 'Shown Deye 8kW')->value('is_active'));

        $this->get('/companies/re-energy')
            ->assertOk()
            ->assertSee('Shown Deye 8kW')
            ->assertDontSee('Hidden Deye 5kW');
    }

    public function test_hiding_and_showing_an_existing_product(): void
    {
        $this->post(route('company.products.store'), $this->productForm('Growatt 6kW', true));
        $product = Product::query()->where('name', 'Growatt 6kW')->firstOrFail();

        $this->put(route('company.products.update', $product->id), $this->productForm('Growatt 6kW', false))
            ->assertRedirect();
        $this->get('/companies/re-energy')->assertOk()->assertDontSee('Growatt 6kW');

        $this->put(route('company.products.update', $product->id), $this->productForm('Growatt 6kW', true));
        $this->get('/companies/re-energy')->assertOk()->assertSee('Growatt 6kW');
    }

    public function test_the_form_sends_a_value_when_the_box_is_unticked(): void
    {
        $this->get(route('company.products.create'))
            ->assertOk()
            ->assertSee('<input type="hidden" name="is_active" value="0">', false);
    }
}
