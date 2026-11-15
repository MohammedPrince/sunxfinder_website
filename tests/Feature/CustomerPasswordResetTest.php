<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin "Reset password" on admin/customers: the password becomes "{username}@SUNX09*#".
 * Run with: vendor/bin/phpunit -c phpunit.mysql.xml
 */
class CustomerPasswordResetTest extends TestCase
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

    private function customer(array $extra = []): Customer
    {
        return Customer::query()->forceCreate(array_merge([
            'username' => 'mohamed',
            'password' => Hash::make('Forgotten1!'),
            'full_name' => 'Mohamed Ahmed',
            'phone' => '0911111111',
            'email' => 'mohamed@example.test',
            'city' => 'Khartoum',
            'status' => 'active',
            'is_active' => true,
        ], $extra));
    }

    private function asAdmin(): static
    {
        $admin = Admin::query()->where('username', 'admin')->first()
            ?? Admin::query()->forceCreate(['username' => 'admin', 'password' => Hash::make('secret123'), 'is_active' => true]);

        return $this->actingAs($admin, 'admin');
    }

    public function test_customer_list_shows_the_reset_button(): void
    {
        $customer = $this->customer();

        $this->asAdmin()
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee(route('admin.customers.reset-password', $customer->id), false)
            ->assertSee('mohamed@SUNX09*#', false);
    }

    public function test_admin_resets_the_password_and_signs_the_customer_out(): void
    {
        $customer = $this->customer();
        $customer->createToken('phone');

        $this->asAdmin()
            ->from(route('admin.customers.index'))
            ->post(route('admin.customers.reset-password', $customer->id))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', fn($message) => str_contains($message, 'mohamed@SUNX09*#'));

        $customer->refresh();
        $this->assertTrue(Hash::check('mohamed@SUNX09*#', $customer->password));
        $this->assertFalse(Hash::check('Forgotten1!', $customer->password));
        $this->assertSame(0, $customer->tokens()->count(), 'Signed out of the app on every device.');
        $this->assertTrue(ActivityLog::query()->where('action', 'password_reset')->where('subject_id', $customer->id)->exists());
    }

    public function test_guest_cannot_reset_a_password(): void
    {
        $customer = $this->customer();

        $this->post(route('admin.customers.reset-password', $customer->id))->assertRedirect();
        $this->assertTrue(Hash::check('Forgotten1!', $customer->refresh()->password));
    }
}
