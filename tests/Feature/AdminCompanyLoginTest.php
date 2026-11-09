<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin → Companies → "Log in as company" opens the company panel in a new tab.
 * Admin and company share one session, so the CSRF token must stay the same:
 * otherwise the next form sent from the admin tab fails with "Page expired" (419).
 */
class AdminCompanyLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (!str_ends_with((string) DB::connection()->getDatabaseName(), '_test')) {
            $this->markTestSkipped('Needs the MySQL test database: vendor/bin/phpunit -c phpunit.mysql.xml');
        }
    }

    private function makeCompany(string $username): SolarCompany
    {
        return SolarCompany::query()->forceCreate([
            'name' => ucfirst($username) . ' Solar',
            'slug' => $username,
            'username' => $username,
            'email' => $username . '@example.test',
            'phone' => '+249900000002',
            'city' => 'Khartoum',
            'license_number' => 'LIC-' . $username,
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
        ]);
    }

    public function test_logging_in_as_companies_keeps_the_admin_tab_forms_valid(): void
    {
        $admin = Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
        $first = $this->makeCompany('redsea');
        $second = $this->makeCompany('nile');

        $this->actingAs($admin, 'admin')->withSession(['_token' => 'admin-tab-token']);

        // Each form in the admin tab still carries "admin-tab-token".
        $this->post(route('admin.companies.login', $first->id), ['_token' => 'admin-tab-token'])
            ->assertRedirect(route('company.dashboard'));
        $this->assertSame('admin-tab-token', session()->token());
        $this->assertSame($first->id, auth('company')->id());

        $this->post(route('admin.companies.login', $second->id), ['_token' => 'admin-tab-token'])
            ->assertRedirect(route('company.dashboard'));
        $this->assertSame('admin-tab-token', session()->token());
        $this->assertSame($second->id, auth('company')->id());

        // Signing out of the company (from its tab) does not expire the admin tab either.
        $this->post(route('company.logout'), ['_token' => 'admin-tab-token']);
        $this->assertSame('admin-tab-token', session()->token());
        $this->assertTrue(auth('admin')->check());
    }
}
