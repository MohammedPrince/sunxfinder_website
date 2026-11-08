<?php

namespace Tests\Feature;

use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerDeviceToken;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\PackageOffer;
use App\Models\PackageOfferResponse;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Models\Supplier;
use App\Repositories\Admin\AdminRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Contract\Messaging;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PackageOfferTest extends TestCase
{
    use RefreshDatabase;

    private SolarCompany $company;
    private Customer $customer;
    private Product $panel;
    private Product $inverter;

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

        Bus::fake();
        // No real pushes: Firebase is never called.
        $this->mock(Messaging::class)->shouldIgnoreMissing();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->company = $this->makeCompany('Red Sea Solar', 'redsea');
        $this->customer = $this->makeCustomer('own');
        $this->makeOwnCustomer($this->customer);

        $supplier = Supplier::query()->forceCreate(['name' => 'Jinko']);
        $this->panel = $this->makeProduct($this->company, $supplier, 'Tiger Neo 550W', 'solar_panel');
        $this->inverter = $this->makeProduct($this->company, $supplier, 'Deye 5kW', 'inverter');

        $this->actingAs($this->company, 'company');
    }

    private function makeCompany(string $name, string $username): SolarCompany
    {
        return SolarCompany::query()->forceCreate([
            'name' => $name,
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
            'on_app' => true,
        ]);
    }

    private function makeCustomer(string $name): Customer
    {
        $customer = Customer::query()->forceCreate([
            'username' => $name,
            'password' => Hash::make('secret123'),
            'full_name' => $name . ' customer',
            'phone' => '+2499' . crc32($name),
            'email' => $name . '@example.test',
            'city' => 'Khartoum',
            'status' => 'active',
            'is_active' => true,
        ]);

        CustomerDeviceToken::query()->create([
            'customer_id' => $customer->id,
            'fcm_token' => 'token-' . $name,
            'token_hash' => hash('sha256', 'token-' . $name),
            'platform' => 'android',
            'lang' => 'en',
            'is_active' => true,
        ]);

        return $customer;
    }

    /** A customer belongs to the company once they accepted one of its offers. */
    private function makeOwnCustomer(Customer $customer): void
    {
        $quote = Quote::query()->forceCreate(['customer_id' => $customer->id, 'quote_name' => 'Old job', 'status' => 'accepted']);

        CompanyQuotation::query()->forceCreate([
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'price' => 1000,
            'status' => 'selected',
        ]);
    }

    private function makeProduct(SolarCompany $company, Supplier $supplier, string $name, string $type): Product
    {
        return Product::query()->forceCreate([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    private function offerPayload(array $overrides = []): array
    {
        return array_merge([
            'title_en' => '5 kW home system',
            'title_ar' => 'منظومة 5 كيلوواط',
            'description_en' => 'Installed in 3 days.',
            'max_load_kw' => '5',
            'currency' => 'SDG',
            'discount' => '100000',
            'items' => [
                ['product_id' => $this->panel->id, 'description' => 'Tiger Neo 550W', 'quantity' => '6', 'unit_price' => '200000'],
                ['product_id' => $this->inverter->id, 'description' => 'Deye 5kW', 'quantity' => '1', 'unit_price' => '900000'],
                ['type' => 'other', 'description' => 'Installation', 'quantity' => '1', 'unit_price' => '300000'],
            ],
        ], $overrides);
    }

    private function createOffer(array $overrides = []): PackageOffer
    {
        $this->post(route('company.offers.store'), $this->offerPayload($overrides))->assertSessionHasNoErrors();

        return PackageOffer::query()->latest('id')->firstOrFail();
    }

    private function sendOffer(PackageOffer $offer, array $customerIds): void
    {
        $this->post(route('company.offers.send', $offer->id), ['customer_ids' => $customerIds])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.offers.show', $offer->id));
    }

    private function acceptPayload(array $overrides = []): array
    {
        return array_merge([
            'estimated_load' => 'Estimated load: 20 kWh/day, peak 4.2 kW.',
            'peak_load' => '4.2 kW',
            'daily_load' => '20 kWh',
            'appliances_ids' => [['id' => 1, 'qty' => 2], ['id' => 4, 'qty' => 1]],
            'message' => 'Please call before visiting.',
        ], $overrides);
    }

    public function test_company_builds_an_offer_from_its_products_with_server_totals(): void
    {
        $offer = $this->createOffer();

        // 6 × 200,000 + 900,000 + 300,000 = 2,400,000, minus 100,000.
        $this->assertSame('2400000.00', $offer->subtotal);
        $this->assertSame('100000.00', $offer->discount);
        $this->assertSame('2300000.00', $offer->price);
        $this->assertSame('5.00', $offer->max_load_kw);
        $this->assertSame('solar_panel', $offer->items[0]['type']);
        $this->assertSame($this->panel->id, $offer->items[0]['product_id']);
        $this->assertSame('inverter', $offer->items[1]['type']);
        $this->assertSame('other', $offer->items[2]['type']);
        $this->assertArrayNotHasKey('product_id', $offer->items[2]);
        $this->assertSame('active', $offer->status);

        $this->get(route('company.offers.index'))->assertOk()->assertSee('5 kW home system');
        $this->get(route('company.offers.show', $offer->id))->assertOk()->assertSee('Tiger Neo 550W');
        $this->get(route('company.offers.create'))->assertOk()->assertSee('Deye 5kW');
    }

    public function test_another_companys_product_cannot_be_used(): void
    {
        $other = $this->makeCompany('Nile Power', 'nile');
        $foreign = $this->makeProduct($other, Supplier::query()->firstOrFail(), 'Foreign panel', 'solar_panel');

        $this->post(route('company.offers.store'), $this->offerPayload([
            'items' => [['product_id' => $foreign->id, 'description' => 'Foreign panel', 'quantity' => '10', 'unit_price' => '500000']],
        ]))->assertSessionHasErrors('items');

        $this->assertSame(0, PackageOffer::query()->count());
    }

    public function test_sending_pushes_the_offer_and_locks_it(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);

        $notification = Notification::query()->sole();
        $this->assertSame('offer', $notification->type);
        $this->assertSame($offer->id, (int) $notification->package_offer_id);
        $this->assertSame('2300000.00', $notification->offer_price);
        $this->assertSame('2400000.00', $notification->offer_old_price);
        $this->assertNotNull($offer->fresh()->sent_at);

        // Customers hold a copy now: no edits, no delete.
        $this->get(route('company.offers.edit', $offer->id))->assertRedirect(route('company.offers.show', $offer->id));
        $this->put(route('company.offers.update', $offer->id), $this->offerPayload(['title_en' => 'Changed']))
            ->assertSessionHasErrors('title_en');
        $this->delete(route('company.offers.destroy', $offer->id));
        $this->assertNotNull($offer->fresh());

        // Only the company's own customers, as for other company notifications.
        $stranger = $this->makeCustomer('stranger');
        $this->post(route('company.offers.send', $offer->id), ['customer_ids' => [$stranger->id]])
            ->assertSessionHasErrors('customer_ids');
    }

    public function test_customer_sees_the_offer_in_the_notification_and_details(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);

        Sanctum::actingAs($this->customer);

        $item = $this->getJson('/api/v1/customer/notifications')->assertOk()->json('data.notifications.0');

        $this->assertSame('offer', $item['type']);
        $this->assertSame('package_offer', $item['action']['type']);
        $this->assertSame('sunxfinder://package-offer/' . $offer->id, $item['action']['url']);
        $this->assertSame($offer->id, $item['package_offer']['id']);
        $this->assertEquals(5, $item['package_offer']['max_load_kw']);
        $this->assertSame('active', $item['package_offer']['status']);
        $this->assertTrue($item['package_offer']['can_respond']);
        $this->assertNull($item['package_offer']['my_response']);
        $this->assertEquals(2300000, $item['offer']['price']);
        $this->assertSame(4, $item['offer']['discount_percent']);

        $details = $this->getJson('/api/v1/customer/package-offers/' . $offer->id)
            ->assertOk()
            ->json('data.package_offer');

        $this->assertSame('5 kW home system', $details['title']);
        $this->assertSame('منظومة 5 كيلوواط', $details['ar_title']);
        $this->assertSame('Red Sea Solar', $details['company']['name']);
        $this->assertCount(3, $details['items']);
        $this->assertSame('solar_panel', $details['items'][0]['type']);
        $this->assertEquals(1200000, $details['items'][0]['line_total']);
        $this->assertEquals(2300000, $details['price']);
    }

    public function test_customer_who_was_not_sent_the_offer_gets_404(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);

        Sanctum::actingAs($this->makeCustomer('stranger'));

        $this->getJson('/api/v1/customer/package-offers/' . $offer->id)->assertNotFound();
        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload())->assertNotFound();
    }

    public function test_a_load_above_the_offer_limit_is_refused(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload([
            'peak_load' => '6.3 kW',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('data.reason', 'load_exceeds_offer')
            ->assertJsonPath('data.max_load_kw', 5);

        // peak_load_kw wins over the text.
        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload([
            'peak_load' => '4 kW',
            'peak_load_kw' => 5.2,
        ]))->assertStatus(422);

        $this->assertSame(0, Quote::query()->whereNotNull('package_offer_id')->count());
    }

    public function test_accepting_sends_the_devices_to_the_company_only_for_review(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);

        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload())
            ->assertCreated()
            ->assertJsonPath('data.package_offer.my_response.status', 'accepted')
            ->assertJsonPath('data.package_offer.can_respond', false);

        $quote = Quote::query()->findOrFail($response->json('data.quote.id'));

        $this->assertSame('pending', $quote->status);
        $this->assertSame($this->company->id, (int) $quote->company_id);
        $this->assertSame($offer->id, (int) $quote->package_offer_id);
        $this->assertSame('5 kW home system', $quote->quote_name);
        $this->assertSame([1 => 2, 4 => 1], $quote->applianceQuantities());
        // The company reviews first: no offer, no project yet.
        $this->assertSame(0, CompanyQuotation::query()->where('quote_id', $quote->id)->count());

        // A second tap does not open another request.
        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload())
            ->assertStatus(409)
            ->assertJsonPath('data.reason', 'already_accepted')
            ->assertJsonPath('data.quote_id', $quote->id);

        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/decline')->assertStatus(409);

        $myQuote = collect($this->postJson(route('my-quotes'))->assertOk()->json('data.quotes'))->firstWhere('id', $quote->id);
        $this->assertSame($offer->id, $myQuote['package_offer_id']);

        // The company sees who accepted, and the offer form starts from the package.
        $this->actingAs($this->company, 'company');

        $this->get(route('company.offers.show', $offer->id))
            ->assertOk()
            ->assertSee('own customer')
            ->assertSee(route('company.quote-requests.show', $quote->id));

        $this->get(route('company.quote-requests.show', $quote->id))
            ->assertOk()
            ->assertSee('package-offer-banner', false)
            ->assertSee('value="' . $this->panel->id . '" data-type="solar_panel"', false)
            ->assertSee('Installed in 3 days.');

        // Another company cannot see this request.
        $this->actingAs($this->makeCompany('Nile Power', 'nile'), 'company');
        $this->get(route('company.quote-requests.show', $quote->id))->assertRedirect(route('company.quote-requests.index'));
    }

    public function test_decline_can_later_become_accept(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/decline')
            ->assertOk()
            ->assertJsonPath('data.package_offer.my_response.status', 'declined')
            ->assertJsonPath('data.package_offer.can_respond', true);

        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload())->assertCreated();

        $this->assertSame('accepted', PackageOfferResponse::query()->sole()->status);
    }

    public function test_a_closed_offer_cannot_be_accepted(): void
    {
        $offer = $this->createOffer();
        $this->sendOffer($offer, [$this->customer->id]);
        $this->post(route('company.offers.close', $offer->id));

        Sanctum::actingAs($this->customer);

        $this->getJson('/api/v1/customer/package-offers/' . $offer->id)
            ->assertOk()
            ->assertJsonPath('data.package_offer.status', 'closed')
            ->assertJsonPath('data.package_offer.can_respond', false);

        $this->postJson('/api/v1/customer/package-offers/' . $offer->id . '/accept', $this->acceptPayload())
            ->assertStatus(409)
            ->assertJsonPath('data.reason', 'offer_closed');
    }

    public function test_quote_offer_invoice_lines_keep_the_picked_product(): void
    {
        $quote = Quote::query()->forceCreate(['customer_id' => $this->customer->id, 'quote_name' => 'Home', 'status' => 'pending']);

        $this->post(route('company.quote-requests.offer.store', $quote->id), [
            'currency' => 'SDG',
            'message' => 'Our offer.',
            'ar_message' => 'عرضنا.',
            'include_invoice' => '1',
            'invoice_number' => 'INV-77',
            'invoice_date' => '2026-11-08',
            'invoice_items' => [
                ['product_id' => $this->panel->id, 'description' => 'Tiger Neo 550W', 'quantity' => '4', 'unit_price' => '200000'],
                ['description' => 'Cables', 'quantity' => '1', 'unit_price' => '50000'],
            ],
        ])->assertSessionHasNoErrors();

        $items = Invoice::query()->sole()->items;

        $this->assertSame($this->panel->id, $items[0]['product_id']);
        $this->assertSame('solar_panel', $items[0]['type']);
        $this->assertArrayNotHasKey('product_id', $items[1]);
    }

    public function test_admin_campaign_can_carry_a_company_package_offer(): void
    {
        $offer = $this->createOffer();
        $stranger = $this->makeCustomer('stranger');

        $notification = app(AdminRepository::class)->publishCampaign([
            'type' => 'offer',
            'audience_type' => 'selected',
            'customer_ids' => [$stranger->id],
            'title_en' => 'Big week',
            'format' => 'text',
            'message_en' => 'A ready system from Red Sea Solar.',
            'company_id' => $this->company->id,
            'package_offer_id' => $offer->id,
            'has_offer' => false,
            // Hand-typed card values are replaced by the offer's.
            'offer_price' => 1,
        ], null, null);

        $this->assertSame($offer->id, (int) $notification->package_offer_id);
        $this->assertSame('2300000.00', $notification->offer_price);
        $this->assertNotNull($offer->fresh()->sent_at);

        Sanctum::actingAs($stranger);
        $this->getJson('/api/v1/customer/package-offers/' . $offer->id)->assertOk();
    }

    public function test_load_text_is_read_in_kw(): void
    {
        $this->assertSame(4.2, PackageOffer::loadToKw('4.2 kW'));
        $this->assertSame(3.5, PackageOffer::loadToKw('3500 W'));
        $this->assertSame(4.0, PackageOffer::loadToKw('٤ كيلوواط'));
        $this->assertSame(5.0, PackageOffer::loadToKw(5));
        $this->assertNull(PackageOffer::loadToKw('none'));
    }
}
