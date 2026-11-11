<?php

namespace Tests\Feature;

use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Warranty and terms on a company's offer: form → saved JSON → company page, invoice, customer API. */
class CompanyOfferTermsTest extends TestCase
{
    use RefreshDatabase;

    private SolarCompany $company;
    private Customer $customer;
    private Quote $quote;

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

        $this->customer = Customer::query()->forceCreate([
            'username' => 'mohamed',
            'password' => Hash::make('secret123'),
            'full_name' => 'Mohamed Ali',
            'phone' => '+249900000001',
            'email' => 'customer@example.test',
            'city' => 'Khartoum',
        ]);

        $this->quote = $this->makeQuote();

        $this->actingAs($this->company, 'company');
    }

    private function makeQuote(): Quote
    {
        return Quote::query()->forceCreate([
            'customer_id' => $this->customer->id,
            'quote_name' => 'Home system',
            'status' => 'pending',
        ]);
    }

    private function terms(array $overrides = []): array
    {
        return array_replace_recursive([
            'warranties' => [
                'panels' => ['value' => '25', 'unit' => 'years'],
                'inverter' => ['value' => '5', 'unit' => 'years'],
                'batteries' => ['value' => '', 'unit' => 'years'],
                'installation' => ['value' => '12', 'unit' => 'months'],
            ],
            'installation_days' => '3',
            'free_maintenance_months' => '',
            'payment_terms' => 'deposit_50',
            'validity_days' => '14',
            'included' => ['delivery', 'installation', 'testing'],
            'extras' => [['en' => 'Free 24/7 phone support', 'ar' => 'دعم هاتفي مجاني']],
        ], $overrides);
    }

    private function submitOffer(array $overrides = [], ?Quote $quote = null)
    {
        return $this->post(route('company.quote-requests.offer.store', ($quote ?? $this->quote)->id), array_merge([
            'price' => '5000',
            'currency' => 'SDG',
            'message' => 'Our offer.',
            'ar_message' => 'عرضنا.',
            'include_invoice' => '0',
            'terms' => $this->terms(),
        ], $overrides));
    }

    public function test_offer_saves_the_terms_it_was_given(): void
    {
        $this->submitOffer()->assertSessionHasNoErrors();

        // assertEquals: MySQL's JSON type reorders object keys; list order is kept.
        $this->assertEquals([
            'warranties' => [
                'panels' => ['value' => 25, 'unit' => 'years'],
                'inverter' => ['value' => 5, 'unit' => 'years'],
                'installation' => ['value' => 12, 'unit' => 'months'],
            ],
            'installation_days' => 3,
            'validity_days' => 14,
            'payment_terms' => 'deposit_50',
            'included' => ['delivery', 'installation', 'testing'],
            'extras' => [['en' => 'Free 24/7 phone support', 'ar' => 'دعم هاتفي مجاني']],
        ], CompanyQuotation::query()->sole()->terms);
    }

    public function test_offer_without_terms_stores_null(): void
    {
        $this->submitOffer(['terms' => null])->assertSessionHasNoErrors();

        $this->assertNull(CompanyQuotation::query()->sole()->terms);
    }

    public function test_invalid_terms_are_rejected(): void
    {
        $this->submitOffer(['terms' => $this->terms([
            'warranties' => ['panels' => ['value' => '-1', 'unit' => 'decades']],
            'payment_terms' => 'never',
            'included' => ['free-car'],
        ])])->assertSessionHasErrors([
            'terms.warranties.panels.value',
            'terms.warranties.panels.unit',
            'terms.payment_terms',
            'terms.included.0',
        ]);

        $this->assertSame(0, CompanyQuotation::query()->count());
    }

    public function test_editing_replaces_the_terms(): void
    {
        $this->submitOffer();
        $this->submitOffer(['terms' => ['installation_days' => '7']])->assertSessionHasNoErrors();

        $this->assertSame(['installation_days' => 7], CompanyQuotation::query()->sole()->terms);
    }

    public function test_a_new_offer_form_starts_from_the_last_offers_terms(): void
    {
        $this->submitOffer();

        $this->get(route('company.quote-requests.show', $this->makeQuote()->id))
            ->assertOk()
            ->assertSee(__('company/messages.offer_terms.prefilled_note'))
            ->assertSee('value="25"', false)
            ->assertSee('Free 24/7 phone support');
    }

    public function test_company_page_and_invoice_show_the_terms(): void
    {
        $this->submitOffer([
            'include_invoice' => '1',
            'invoice_number' => 'INV-1001',
            'invoice_date' => '2026-11-11',
            'invoice_items' => [['description' => 'Solar panels', 'quantity' => '4', 'unit_price' => '500']],
        ])->assertSessionHasNoErrors();

        $this->get(route('company.quote-requests.show', $this->quote->id))
            ->assertOk()
            ->assertSee('Panels warranty')
            ->assertSee('25 years');

        // The invoice's payment terms follow the picked option.
        $this->assertSame('50% deposit, 50% after installation', Invoice::query()->sole()->payment_terms);

        $this->get(route('company.quote-requests.invoice', $this->quote->id))
            ->assertOk()
            ->assertSee('Warranty and terms')
            ->assertSee('Within 3 days');

        $this->get(route('company.quote-requests.invoice.pdf', $this->quote->id))->assertOk();
    }

    public function test_admin_quote_page_shows_the_terms(): void
    {
        $this->submitOffer();

        $admin = \App\Models\Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.quote-requests.show', $this->quote->id))
            ->assertOk()
            ->assertSee('Warranty and terms (8)')
            ->assertSee('25 years');
    }

    public function test_my_quotes_returns_terms_terms_display_and_valid_until(): void
    {
        $this->submitOffer();
        $this->submitOffer(['terms' => null], $plain = $this->makeQuote());

        Sanctum::actingAs($this->customer);

        $quotes = collect($this->postJson(route('my-quotes'))->assertOk()->json('data.quotes'))->keyBy('id');
        $offer = $quotes[$this->quote->id]['offers'][0];

        $this->assertSame(25, $offer['terms']['warranties']['panels']['value']);
        $this->assertSame('deposit_50', $offer['terms']['payment_terms']);
        $this->assertSame([
            'key' => 'warranty_panels',
            'label' => 'Panels warranty',
            'ar_label' => 'ضمان الألواح',
            'value' => '25 years',
            'ar_value' => '25 سنة',
        ], $offer['terms_display'][0]);
        $this->assertSame(
            ['warranty_panels', 'warranty_inverter', 'warranty_installation', 'installation_time', 'payment_terms', 'included', 'extra', 'validity'],
            array_column($offer['terms_display'], 'key')
        );
        $saved = CompanyQuotation::query()->where('quote_id', $this->quote->id)->sole();
        $this->assertSame($saved->updated_at->copy()->addDays(14)->toDateString(), $offer['valid_until']);

        $plainOffer = $quotes[$plain->id]['offers'][0];
        $this->assertNull($plainOffer['terms']);
        $this->assertSame([], $plainOffer['terms_display']);
        $this->assertNull($plainOffer['valid_until']);
    }
}
