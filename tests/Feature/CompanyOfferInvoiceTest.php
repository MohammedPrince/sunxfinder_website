<?php

namespace Tests\Feature;

use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Support\InvoicePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyOfferInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private SolarCompany $company;
    private Customer $customer;
    private Quote $quote;

    /**
     * The migrations are MySQL-only, and RefreshDatabase wipes the database it runs on,
     * so these tests only run against a dedicated one: vendor/bin/phpunit -c phpunit.mysql.xml
     */
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

        $this->company = $this->makeCompany('Red Sea Solar', 'redsea');

        $this->customer = Customer::query()->forceCreate([
            'username' => 'mohamed',
            'password' => Hash::make('secret123'),
            'full_name' => 'محمد أحمد علي',
            'phone' => '+249900000001',
            'email' => 'customer@example.test',
            'city' => 'Khartoum',
            'address' => 'بحري، كوبر',
        ]);

        $this->quote = $this->makeQuote();

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
        ]);
    }

    private function makeQuote(): Quote
    {
        return Quote::query()->forceCreate([
            'customer_id' => $this->customer->id,
            'quote_name' => 'Home system',
            'status' => 'pending',
        ]);
    }

    private function offerPayload(array $overrides = []): array
    {
        return array_merge([
            'price' => '0.00',
            'currency' => 'USD',
            'message' => 'Our offer.',
            'ar_message' => 'عرضنا.',
            'include_invoice' => '1',
            'invoice_number' => 'INV-1001',
            'invoice_date' => '2026-10-31',
            'payment_terms' => 'Due upon acceptance',
            'invoice_notes' => 'Pay by bank transfer.',
            'invoice_discount' => '100',
            'invoice_tax_rate' => '10',
            'invoice_shipping' => '50',
            'invoice_items' => [
                ['description' => 'Solar panels', 'quantity' => '4', 'unit_price' => '500'],
                ['description' => 'تركيب واختبار المنظومة', 'quantity' => '1', 'unit_price' => '300.50'],
            ],
        ], $overrides);
    }

    private function submitOffer(array $overrides = [], ?Quote $quote = null)
    {
        return $this->post(
            route('company.quote-requests.offer.store', ($quote ?? $this->quote)->id),
            $this->offerPayload($overrides)
        );
    }

    public function test_offer_saves_invoice_row_and_uses_server_calculated_total(): void
    {
        $this->submitOffer()->assertRedirect(route('company.quote-requests.show', $this->quote->id));

        $offer = CompanyQuotation::query()->firstOrFail();
        $invoice = Invoice::query()->firstOrFail();

        // 2300.50 - 100 = 2200.50, + 10% tax 220.05, + 50 shipping.
        $this->assertSame('2300.50', $invoice->subtotal);
        $this->assertSame('220.05', $invoice->tax);
        $this->assertSame('2470.55', $invoice->balance_due);
        $this->assertSame('2470.55', $offer->price);

        $this->assertSame($offer->id, $invoice->company_quotation_id);
        $this->assertSame($this->company->id, $invoice->company_id);
        $this->assertSame('USD', $invoice->currency);
        $this->assertSame('محمد أحمد علي', $invoice->customer_details['name']);
        $this->assertCount(2, $invoice->items);
    }

    public function test_offer_without_invoice_keeps_the_typed_price(): void
    {
        $this->submitOffer(['include_invoice' => '0', 'price' => '5000'])->assertSessionHasNoErrors();

        $this->assertSame('5000.00', CompanyQuotation::query()->firstOrFail()->price);
        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_invoice_total_below_the_minimum_is_rejected_and_nothing_is_saved(): void
    {
        $this->submitOffer(['invoice_items' => [
            ['description' => 'Solar panels', 'quantity' => '1', 'unit_price' => '10'],
        ], 'invoice_discount' => '0', 'invoice_shipping' => '0'])->assertSessionHasErrors('invoice_items');

        $this->assertSame(0, CompanyQuotation::query()->count());
        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_editing_updates_the_same_invoice_row(): void
    {
        $this->submitOffer();
        $this->submitOffer(['invoice_discount' => '0', 'invoice_number' => 'INV-1002'])->assertSessionHasNoErrors();

        $invoice = Invoice::query()->sole();

        $this->assertSame('INV-1002', $invoice->invoice_number);
        $this->assertSame('2580.55', $invoice->balance_due);
    }

    public function test_turning_the_invoice_off_soft_deletes_it_and_turning_it_on_restores_it(): void
    {
        $this->submitOffer();

        $this->submitOffer(['include_invoice' => '0', 'price' => '5000'])->assertSessionHasNoErrors();
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(1, Invoice::withTrashed()->count());
        $this->get(route('company.quote-requests.invoice', $this->quote->id))
            ->assertRedirect(route('company.quote-requests.show', $this->quote->id));

        $this->submitOffer()->assertSessionHasNoErrors();
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, Invoice::withTrashed()->count());
    }

    public function test_invoice_number_cannot_repeat_within_a_company(): void
    {
        $this->submitOffer();

        $this->submitOffer([], $this->makeQuote())->assertSessionHasErrors('invoice_number');

        $this->assertSame(1, CompanyQuotation::query()->count());
    }

    public function test_two_companies_can_use_the_same_invoice_number(): void
    {
        $this->submitOffer();

        $this->actingAs($this->makeCompany('Nile Power', 'nile'), 'company');
        $this->submitOffer()->assertSessionHasNoErrors();

        $this->assertSame(2, Invoice::query()->count());
    }

    public function test_company_downloads_its_invoice_as_a_pdf(): void
    {
        $this->submitOffer();

        $response = $this->get(route('company.quote-requests.invoice.pdf', $this->quote->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inv-1001.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->streamedContent());
    }

    public function test_pdf_filename_falls_back_when_the_number_has_no_latin_characters(): void
    {
        $invoice = new Invoice(['invoice_number' => 'فاتورة']);
        $invoice->id = 7;

        $this->assertSame('invoice-7.pdf', InvoicePdf::filename($invoice));
    }

    public function test_another_company_cannot_view_or_download_the_invoice(): void
    {
        $this->submitOffer();

        $this->actingAs($this->makeCompany('Nile Power', 'nile'), 'company');

        $this->get(route('company.quote-requests.invoice', $this->quote->id))
            ->assertRedirect(route('company.quote-requests.show', $this->quote->id));
        $this->get(route('company.quote-requests.invoice.pdf', $this->quote->id))
            ->assertRedirect(route('company.quote-requests.show', $this->quote->id));
    }

    public function test_quote_page_and_invoice_page_render(): void
    {
        $this->get(route('company.quote-requests.show', $this->quote->id))
            ->assertOk()
            ->assertSee('invoiceItemTemplate')
            ->assertDontSee('Download Invoice PDF');

        $this->submitOffer();

        $this->get(route('company.quote-requests.show', $this->quote->id))
            ->assertOk()
            ->assertSee('INV-1001')
            ->assertSee('Download Invoice PDF');

        $this->get(route('company.quote-requests.invoice', $this->quote->id))
            ->assertOk()
            ->assertSee('2,470.55 USD');
    }

    public function test_my_quotes_returns_invoice_details_with_the_offer(): void
    {
        $this->submitOffer();

        $withoutInvoice = $this->makeQuote();
        $this->submitOffer(['include_invoice' => '0', 'price' => '5000'], $withoutInvoice);

        Sanctum::actingAs($this->customer);

        $response = $this->postJson(route('my-quotes'));
        $response->assertOk();

        $quotes = collect($response->json('data.quotes'))->keyBy('id');

        $invoice = $quotes[$this->quote->id]['offers'][0]['invoice'];

        $this->assertSame('INV-1001', $invoice['invoice_number']);
        $this->assertSame('2026-10-31', $invoice['invoice_date']);
        $this->assertSame('Due upon acceptance', $invoice['payment_terms']);
        $this->assertSame('Pay by bank transfer.', $invoice['payment_instructions']);
        $this->assertSame('USD', $invoice['currency']);
        $this->assertSame('Red Sea Solar', $invoice['company']['name']);
        $this->assertSame('محمد أحمد علي', $invoice['customer']['name']);
        $this->assertSame('Solar panels', $invoice['items'][0]['description']);
        $this->assertEquals(2000, $invoice['items'][0]['total']);
        $this->assertEquals(2300.5, $invoice['subtotal']);
        $this->assertEquals(100, $invoice['discount']);
        $this->assertEquals(2200.5, $invoice['subtotal_less_discount']);
        $this->assertEquals(10, $invoice['tax_rate']);
        $this->assertEquals(220.05, $invoice['tax']);
        $this->assertEquals(50, $invoice['shipping']);
        $this->assertEquals(2470.55, $invoice['balance_due']);

        $this->assertNull($quotes[$withoutInvoice->id]['offers'][0]['invoice']);
    }

    public function test_offer_on_an_open_request_notifies_the_customer_with_the_quote_id(): void
    {
        $this->assertNull($this->quote->company_id);

        $this->submitOffer()->assertSessionHasNoErrors();

        Sanctum::actingAs($this->customer);

        $notification = $this->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->json('data.notifications.0');

        $this->assertSame('offer_received', $notification['type']);
        $this->assertSame($this->quote->id, $notification['quote_id']);
        $this->assertSame('New offer on your request', $notification['title']);
        $this->assertSame('عرض جديد على طلبك', $notification['ar_title']);
        $this->assertFalse($notification['read']);

        $this->getJson('/api/v1/customer/notifications/' . $notification['id'])
            ->assertOk()
            ->assertJsonPath('data.notification.quote_id', $this->quote->id);

        $mainData = $this->postJson('/api/v1/customer/main-data')->assertOk();

        $this->assertSame(1, $mainData->json('data.notifications.unread_count'));
        $this->assertSame($notification, $mainData->json('data.notifications.items.0'));
    }

    public function test_editing_an_offer_keeps_the_quote_id_but_is_not_offer_received(): void
    {
        $this->submitOffer();
        $this->submitOffer(['invoice_discount' => '0', 'invoice_number' => 'INV-1002']);

        Sanctum::actingAs($this->customer);

        $notification = $this->getJson('/api/v1/customer/notifications')->json('data.notifications.0');

        $this->assertSame('offer_updated', $notification['type']);
        $this->assertSame($this->quote->id, $notification['quote_id']);
    }

    public function test_my_quotes_and_main_data_return_the_offer_breakdown(): void
    {
        $this->submitOffer();

        $withoutInvoice = $this->makeQuote();
        $this->submitOffer(['include_invoice' => '0', 'price' => '5000'], $withoutInvoice);

        Sanctum::actingAs($this->customer);

        $quotes = collect($this->postJson(route('my-quotes'))->assertOk()->json('data.quotes'))->keyBy('id');
        $offer = $quotes[$this->quote->id]['offers'][0];

        $this->assertSame('Solar panels', $offer['items'][0]['description']);
        $this->assertNull($offer['items'][0]['ar_description']);
        $this->assertEquals(4, $offer['items'][0]['quantity']);
        $this->assertSame('500.00', $offer['items'][0]['unit_price']);
        $this->assertSame('2000.00', $offer['items'][0]['line_total']);
        $this->assertSame('2300.50', $offer['subtotal']);
        $this->assertSame('100.00', $offer['discount']);
        $this->assertSame('10', $offer['tax_percent']);
        $this->assertSame('220.05', $offer['tax']);
        $this->assertSame('50.00', $offer['shipping']);
        $this->assertSame('2470.55', $offer['total']);

        $plainOffer = $quotes[$withoutInvoice->id]['offers'][0];

        $this->assertSame([], $plainOffer['items']);
        $this->assertSame('5000.00', $plainOffer['subtotal']);
        $this->assertNull($plainOffer['discount']);
        $this->assertNull($plainOffer['tax_percent']);
        $this->assertNull($plainOffer['tax']);
        $this->assertNull($plainOffer['shipping']);
        $this->assertSame('5000.00', $plainOffer['total']);

        $mainQuotes = collect($this->postJson('/api/v1/customer/main-data')->assertOk()->json('data.my_quotes.quotes'))
            ->keyBy('id');

        $this->assertSame($offer, $mainQuotes[$this->quote->id]['offers'][0]);
    }
}
