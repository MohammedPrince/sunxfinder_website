<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Admin → Companies → Edit: the public profile link (/companies/{slug}) can be changed. */
class AdminCompanySlugTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (!str_ends_with((string) DB::connection()->getDatabaseName(), '_test')) {
            $this->markTestSkipped('Needs the MySQL test database: vendor/bin/phpunit -c phpunit.mysql.xml');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $admin = Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');
    }

    private function makeCompany(string $slug): SolarCompany
    {
        return SolarCompany::query()->forceCreate([
            'name' => 'RE Energy Solar',
            'slug' => $slug,
            'username' => $slug,
            'email' => $slug . '@example.test',
            'phone' => '+249900000002',
            'city' => 'Port Sudan',
            'license_number' => 'LIC-' . $slug,
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
        ]);
    }

    private function form(SolarCompany $company, array $changes = []): array
    {
        return array_merge([
            'name' => $company->name,
            'username' => $company->username,
            'email' => $company->email,
            'phone' => $company->phone,
            'city' => $company->city,
            'license_number' => $company->license_number,
            'services' => 'Supply, Installation',
            'ar_services' => 'توريد، تركيب',
            'status' => 'approved',
            'is_verified' => 1,
            'is_active' => 1,
            'year_of_exp' => 5,
            'number_of_projects' => 10,
            'commission_rate' => 5,
        ], $changes);
    }

    public function test_the_edit_form_shows_the_slug(): void
    {
        $company = $this->makeCompany('re-energy-ry-anrgy');

        $this->get(route('admin.companies.edit', $company->id))
            ->assertOk()
            ->assertSee('name="slug"', false)
            ->assertSee('value="re-energy-ry-anrgy"', false);
    }

    public function test_admin_changes_the_slug_and_the_profile_moves(): void
    {
        $company = $this->makeCompany('re-energy-ry-anrgy');

        // Capitals and spaces are tidied into a valid slug.
        $this->put(route('admin.companies.update', $company->id), $this->form($company, ['slug' => ' RE Energy ']))
            ->assertRedirect(route('admin.companies.index'));

        $this->assertSame('re-energy', $company->fresh()->slug);
        $this->get('/companies/re-energy')->assertOk();
        $this->get('/companies/re-energy-ry-anrgy')->assertNotFound();
    }

    public function test_slug_must_be_valid_and_unique(): void
    {
        $company = $this->makeCompany('re-energy-ry-anrgy');
        $this->makeCompany('nile-solar');

        $this->from(route('admin.companies.edit', $company->id))
            ->put(route('admin.companies.update', $company->id), $this->form($company, ['slug' => 'ري-انرجي']))
            ->assertSessionHasErrors('slug');

        $this->from(route('admin.companies.edit', $company->id))
            ->put(route('admin.companies.update', $company->id), $this->form($company, ['slug' => 'nile-solar']))
            ->assertSessionHas('error', 'Another company already uses this profile link (slug).');

        $this->assertSame('re-energy-ry-anrgy', $company->fresh()->slug);
    }

    public function test_keeping_the_same_slug_or_leaving_it_out_still_saves(): void
    {
        $company = $this->makeCompany('re-energy');

        $this->put(route('admin.companies.update', $company->id), $this->form($company, ['slug' => 're-energy', 'name' => 'RE Energy Co']))
            ->assertRedirect(route('admin.companies.index'));
        $this->put(route('admin.companies.update', $company->id), $this->form($company, ['name' => 'RE Energy Company']))
            ->assertRedirect(route('admin.companies.index'));

        $this->assertSame('re-energy', $company->fresh()->slug);
        $this->assertSame('RE Energy Company', $company->fresh()->name);
    }
}
