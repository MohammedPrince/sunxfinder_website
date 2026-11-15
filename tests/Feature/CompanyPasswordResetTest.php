<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\SolarCompany;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin "Reset password" on admin/companies: the password becomes "{username}@SUNX09*#".
 * Run with: vendor/bin/phpunit -c phpunit.mysql.xml
 */
class CompanyPasswordResetTest extends TestCase
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
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function company(array $extra = []): SolarCompany
    {
        return SolarCompany::query()->forceCreate(array_merge([
            'name' => 'RE Energy',
            'slug' => 're-energy',
            'username' => 'reenergy',
            'email' => 'reenergy@example.test',
            'phone' => '+249912345678',
            'city' => 'Khartoum',
            'license_number' => 'LIC-RE',
            'password' => Hash::make('forgotten-123'),
            'remember_token' => 'old-remember-token',
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
        ], $extra));
    }

    private function asAdmin(): static
    {
        $admin = Admin::query()->where('username', 'admin')->first()
            ?? Admin::query()->forceCreate(['username' => 'admin', 'password' => Hash::make('secret123'), 'is_active' => true]);

        return $this->actingAs($admin, 'admin');
    }

    public function test_directory_shows_the_reset_button(): void
    {
        $company = $this->company();

        $this->asAdmin()
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee(route('admin.companies.reset-password', $company->id), false)
            ->assertSee('reenergy@SUNX09*#', false);
    }

    public function test_admin_resets_the_password_to_the_default(): void
    {
        $company = $this->company();

        $this->asAdmin()
            ->from(route('admin.companies.index'))
            ->post(route('admin.companies.reset-password', $company->id))
            ->assertRedirect(route('admin.companies.index'))
            ->assertSessionHas('success', fn($message) => str_contains($message, 'reenergy@SUNX09*#'));

        $company->refresh();
        $this->assertTrue(Hash::check('reenergy@SUNX09*#', $company->password));
        $this->assertFalse(Hash::check('forgotten-123', $company->password));
        $this->assertNotSame('old-remember-token', $company->remember_token, 'Remembered devices are signed out.');
        $this->assertTrue(ActivityLog::query()->where('action', 'password_reset')->where('subject_id', $company->id)->exists());

        // The company can sign in with the new password.
        $this->assertTrue(auth()->guard('company')->validate(['username' => 'reenergy', 'password' => 'reenergy@SUNX09*#']));
    }

    public function test_company_without_username_is_not_reset(): void
    {
        $company = $this->company(['username' => '']);
        $before = $company->password;

        $this->asAdmin()
            ->from(route('admin.companies.index'))
            ->post(route('admin.companies.reset-password', $company->id))
            ->assertSessionHas('error');

        $this->assertSame($before, $company->refresh()->password);
    }

    public function test_guest_cannot_reset_a_password(): void
    {
        $company = $this->company();

        $this->post(route('admin.companies.reset-password', $company->id))->assertRedirect();
        $this->assertTrue(Hash::check('forgotten-123', $company->refresh()->password));
    }
}
