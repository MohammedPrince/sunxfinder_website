<?php

namespace Tests\Feature;

use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerDeviceToken;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Repositories\Admin\AdminRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyNotificationAudienceTest extends TestCase
{
    use RefreshDatabase;

    private SolarCompany $company;
    private Customer $ownCustomer;
    private Customer $otherCustomer;

    /** See CompanyOfferInvoiceTest: run with vendor/bin/phpunit -c phpunit.mysql.xml */
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

        $this->company = SolarCompany::query()->forceCreate([
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

        $this->ownCustomer = $this->makeCustomer('own');
        $this->otherCustomer = $this->makeCustomer('other');

        $this->acceptOfferFrom($this->ownCustomer);

        $this->actingAs($this->company, 'company');
    }

    private function makeCustomer(string $name, array $overrides = [], bool $pushEnabled = true): Customer
    {
        $customer = Customer::query()->forceCreate(array_merge([
            'username' => $name,
            'password' => Hash::make('secret123'),
            'full_name' => $name . ' customer',
            'phone' => '+2499' . crc32($name),
            'email' => $name . '@example.test',
            'city' => 'Khartoum',
            'status' => 'active',
            'is_active' => true,
        ], $overrides));

        CustomerDeviceToken::query()->create([
            'customer_id' => $customer->id,
            'fcm_token' => 'token-' . $name,
            'token_hash' => hash('sha256', 'token-' . $name),
            'platform' => 'android',
            'lang' => 'en',
            'is_active' => $pushEnabled,
        ]);

        return $customer;
    }

    private function acceptOfferFrom(Customer $customer, string $status = 'selected'): void
    {
        $quote = Quote::query()->forceCreate([
            'customer_id' => $customer->id,
            'quote_name' => 'Home system',
            'status' => $status === 'selected' ? 'accepted' : 'pending',
        ]);

        CompanyQuotation::query()->forceCreate([
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'price' => 1000,
            'status' => $status,
        ]);
    }

    public function test_only_reachable_customers_who_accepted_an_offer_are_listed(): void
    {
        $suspended = $this->makeCustomer('suspended', ['status' => 'suspended']);
        $switchedOff = $this->makeCustomer('switched-off', [], false);
        $pending = $this->makeCustomer('pending');
        $lost = $this->makeCustomer('lost');

        $this->acceptOfferFrom($suspended);
        $this->acceptOfferFrom($switchedOff);
        $this->acceptOfferFrom($pending, 'submitted');
        $this->acceptOfferFrom($lost, 'not_selected');

        $this->get(route('company.notifications.create'))
            ->assertOk()
            ->assertSee('own customer')
            ->assertDontSee('other customer')
            ->assertDontSee('suspended customer')
            ->assertDontSee('switched-off customer')
            ->assertDontSee('pending customer')
            ->assertDontSee('lost customer');
    }

    public function test_notify_all_my_customers_preselects_only_customers_who_accepted(): void
    {
        $this->acceptOfferFrom($this->makeCustomer('pending'), 'submitted');

        $this->get(route('company.notifications.create', ['to' => 'company-customers']))
            ->assertOk()
            ->assertViewHas('preselected', [$this->ownCustomer->id]);
    }

    public function test_a_company_cannot_notify_a_customer_who_did_not_accept_its_offer(): void
    {
        $this->acceptOfferFrom($this->otherCustomer, 'submitted');

        $this->post(route('company.notifications.store'), [
            'type' => 'general',
            'audience_type' => 'selected',
            'customer_ids' => [$this->otherCustomer->id],
            'title_en' => 'Hello',
            'message_en' => 'A message',
        ])->assertSessionHasErrors('customer_ids');

        $this->assertDatabaseCount('tbl_notifications', 0);
    }

    public function test_opened_company_lists_all_customers_without_their_contact_details(): void
    {
        $result = app(AdminRepository::class)->setCompanyNotificationAccess($this->company->id, true);

        $this->assertTrue($result['success']);

        $this->get(route('company.notifications.create'))
            ->assertOk()
            ->assertViewHas('canNotifyAll', true)
            // The picker lists names only, never emails.
            ->assertSee('own customer')
            ->assertDontSee('own@example.test')
            ->assertSee('other customer')
            ->assertDontSee('other@example.test')
            ->assertSee('Select all customers');
    }

    public function test_opened_company_lists_only_active_customers_with_a_registered_device(): void
    {
        $this->makeCustomer('switched-off', [], false);
        $this->makeCustomer('suspended', ['status' => 'suspended']);
        $this->makeCustomer('deactivated', ['is_active' => false]);
        $this->makeCustomer('no-device')->deviceTokens()->delete();

        app(AdminRepository::class)->setCompanyNotificationAccess($this->company->id, true);

        $this->get(route('company.notifications.create'))
            ->assertOk()
            ->assertSee('switched-off customer')
            ->assertDontSee('suspended customer')
            ->assertDontSee('deactivated customer')
            ->assertDontSee('no-device customer');
    }

    public function test_setting_the_company_back_restores_the_default(): void
    {
        $admin = app(AdminRepository::class);

        $admin->setCompanyNotificationAccess($this->company->id, true);
        $admin->setCompanyNotificationAccess($this->company->id, false);

        $this->get(route('company.notifications.create'))
            ->assertOk()
            ->assertViewHas('canNotifyAll', false)
            ->assertDontSee('other customer');
    }
}
