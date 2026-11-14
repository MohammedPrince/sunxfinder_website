<?php

namespace Tests\Feature;

use App\Jobs\SendCompanyOfferDecisionEmail;
use App\Mail\CompanyOfferDecisionMail;
use App\Models\Admin;
use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerDeviceToken;
use App\Models\OfferPriceChange;
use App\Models\Project;
use App\Models\ProjectCancellation;
use App\Models\Quote;
use App\Models\SolarCompany;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Contract\Messaging;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A company backing out of an accepted deal, price changes, and the admin decisions
 * (App\Support\OfferWithdrawal, docs/api/customer-offer-withdrawal.md).
 * Run with: vendor/bin/phpunit -c phpunit.mysql.xml
 */
class OfferWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/customer';

    private SolarCompany $company;
    private SolarCompany $other;
    private Customer $customer;

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
        $this->mock(Messaging::class)->shouldIgnoreMissing();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->company = $this->makeCompany('Red Sea Solar', 'redsea');
        $this->other = $this->makeCompany('Nile Power', 'nile');

        $this->customer = Customer::query()->forceCreate([
            'username' => 'mohamed',
            'password' => Hash::make('Secret1!'),
            'full_name' => 'Mohamed Ahmed',
            'phone' => '0911111111',
            'email' => 'mohamed@example.test',
            'city' => 'Khartoum',
            'status' => 'active',
            'is_active' => true,
        ]);

        CustomerDeviceToken::query()->create([
            'customer_id' => $this->customer->id,
            'fcm_token' => 'token-mohamed',
            'token_hash' => hash('sha256', 'token-mohamed'),
            'platform' => 'android',
            'lang' => 'en',
            'is_active' => true,
        ]);
    }

    private function makeCompany(string $name, string $slug): SolarCompany
    {
        return SolarCompany::query()->forceCreate([
            'name' => $name,
            'slug' => $slug,
            'username' => $slug,
            'email' => $slug . '@example.test',
            'phone' => '+2499000' . random_int(10000, 99999),
            'city' => 'Khartoum',
            'license_number' => 'LIC-' . $slug,
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
            'on_app' => true,
            'commission_rate' => 2,
        ]);
    }

    private function offer(Quote $quote, SolarCompany $company, float $price, array $extra = []): CompanyQuotation
    {
        return CompanyQuotation::query()->forceCreate(array_merge([
            'quote_id' => $quote->id,
            'company_id' => $company->id,
            'customer_id' => $quote->customer_id,
            'price' => $price,
            'currency' => 'SDG',
            'status' => 'submitted',
            'commission_type' => CompanyQuotation::COMMISSION_COMPANY,
            'commission_rate' => 2,
            'commission_amount' => $price * 0.02,
        ], $extra));
    }

    /**
     * The customer accepted Red Sea's offer; Nile's offer became not_selected.
     *
     * @return array{0: Quote, 1: CompanyQuotation, 2: CompanyQuotation, 3: Project}
     */
    private function acceptedDeal(): array
    {
        $quote = Quote::query()->forceCreate([
            'customer_id' => $this->customer->id,
            'quote_name' => 'Home system',
            'estimated_load' => 'Estimated load: 20 kWh/day',
            'peak_load' => '4.2 kW',
            'daily_load' => '20 kWh',
            'status' => 'pending',
        ]);
        $won = $this->offer($quote, $this->company, 1500000);
        $lost = $this->offer($quote, $this->other, 1700000);

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/offer-accept', [
            'offer_id' => $won->id,
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
        ])->assertOk();

        $this->assertSame('not_selected', $lost->fresh()->status);

        return [$quote->fresh(), $won->fresh(), $lost->fresh(), Project::query()->where('quote_id', $quote->id)->sole()];
    }

    private function asCompany(?SolarCompany $company = null): static
    {
        return $this->actingAs($company ?? $this->company, 'company');
    }

    private function asAdmin(): static
    {
        $admin = Admin::query()->where('username', 'admin')->first()
            ?? Admin::query()->forceCreate(['username' => 'admin', 'password' => Hash::make('secret123'), 'is_active' => true]);

        return $this->actingAs($admin, 'admin');
    }

    private function myQuote(int $quoteId): array
    {
        Sanctum::actingAs($this->customer);

        return collect($this->postJson(self::API . '/my-quotes')->assertOk()->json('data.quotes'))->firstWhere('id', $quoteId);
    }

    public function test_company_withdraws_before_starting_and_the_request_reopens(): void
    {
        [$quote, $won, $lost, $project] = $this->acceptedDeal();
        $lostUpdatedAt = $lost->updated_at->toISOString();
        $this->travel(1)->hours();

        $this->asCompany()
            ->post(route('company.projects.withdraw', $project->id), ['reason' => 'out_of_stock'])
            ->assertRedirect(route('company.projects.index'))
            ->assertSessionHasNoErrors();

        // The project is gone from every normal lookup, the history stays.
        $this->assertNull(Project::query()->find($project->id));
        $this->assertSame('cancelled', Project::withTrashed()->find($project->id)->status);
        $this->assertSame('withdrawn', $won->fresh()->status);
        $this->assertSame('pending', $quote->fresh()->status);
        $this->assertNull($quote->fresh()->company_id);

        // Nile's offer is back, with its own validity date untouched.
        $lost->refresh();
        $this->assertSame('submitted', $lost->status);
        $this->assertSame($lostUpdatedAt, $lost->updated_at->toISOString());

        $row = ProjectCancellation::query()->sole();
        $this->assertSame(ProjectCancellation::WITHDRAWN, $row->status);
        $this->assertTrue($row->counts_against_company);
        $this->assertSame(1, $row->reopened_offers);

        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $lost->id
            && $job->decision === CompanyOfferDecisionMail::REOPENED);

        // The customer is told, with the request and the company.
        Sanctum::actingAs($this->customer);
        $notification = $this->getJson(self::API . '/notifications')->json('data.notifications.0');
        $this->assertSame('offer_withdrawn', $notification['type']);
        $this->assertSame($quote->id, $notification['quote_id']);
        $this->assertStringContainsString('Red Sea Solar', $notification['body']);
        $this->assertStringContainsString('#Q-', $notification['body']);
        $this->assertStringContainsString('equipment is out of stock', $notification['body']);

        // My Quotes: open again, the reason, and Nile's offer to choose.
        $myQuote = $this->myQuote($quote->id);
        $this->assertSame('pending', $myQuote['status']);
        $this->assertNull($myQuote['project_id']);
        $this->assertSame('out_of_stock', $myQuote['company_withdrawal']['reason']);
        $this->assertSame('Red Sea Solar', $myQuote['company_withdrawal']['company_name']);
        $this->assertSame('المعدات غير متوفرة حالياً', $myQuote['company_withdrawal']['reason_label_ar']);
        $this->assertSame([$lost->id], array_column($myQuote['offers'], 'id'));
        $this->assertSame('submitted', $myQuote['offers'][0]['status']);

        // The customer picks Nile: a new project opens, the banner goes away.
        $this->postJson(self::API . '/offer-accept', [
            'offer_id' => $lost->id,
            'quote_id' => $quote->id,
            'company_id' => $this->other->id,
        ])->assertOk();
        $myQuote = $this->myQuote($quote->id);
        $this->assertSame('accepted', $myQuote['status']);
        $this->assertSame('accepted', $myQuote['project_status']);
        $this->assertNull($myQuote['company_withdrawal']);
        $this->assertSame((int) $this->other->id, (int) Project::query()->where('quote_id', $quote->id)->sole()->company_id);

        // Red Sea can no longer edit its withdrawn offer, and no longer sees the project.
        $this->asCompany()->get(route('company.projects.show', $project->id))->assertNotFound();
    }

    public function test_package_offer_request_reopens_to_all_companies(): void
    {
        [$quote, , , $project] = $this->acceptedDeal();
        // As if it came from a package offer: assigned to this company only.
        $quote->forceFill(['company_id' => $this->company->id])->save();

        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'no_capacity'])->assertSessionHasNoErrors();

        $this->assertNull($quote->fresh()->company_id);
        $this->assertTrue(Quote::query()->visibleToCompany($this->makeCompany('Third Solar', 'third')->id)->whereKey($quote->id)->exists());
    }

    public function test_withdraw_validation(): void
    {
        [, , , $project] = $this->acceptedDeal();

        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'bad'])->assertSessionHasErrors('reason');
        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'other'])->assertSessionHasErrors('note');
        $this->asCompany($this->other)->post(route('company.projects.withdraw', $project->id), ['reason' => 'no_capacity'])->assertNotFound();

        $this->assertSame('accepted', $project->fresh()->status);
        $this->assertSame(0, ProjectCancellation::query()->count());
    }

    public function test_after_work_started_the_company_asks_and_an_admin_approves(): void
    {
        [$quote, $won, $lost, $project] = $this->acceptedDeal();
        $project->forceFill(['status' => 'in_progress', 'started_at' => now(), 'progress_percent' => 40])->save();

        // A request needs an explanation.
        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'site_not_suitable', 'note' => 'Roof'])
            ->assertSessionHasErrors('note');

        $this->asCompany()->post(route('company.projects.withdraw', $project->id), [
            'reason' => 'site_not_suitable',
            'note' => 'The roof cannot carry the panels; we found cracks after starting.',
        ])->assertRedirect(route('company.projects.show', $project->id));

        // Nothing changed yet; the company sees the waiting request.
        $this->assertSame('in_progress', $project->fresh()->status);
        $request = ProjectCancellation::query()->sole();
        $this->assertSame(ProjectCancellation::REQUESTED, $request->status);
        $this->asCompany()->get(route('company.projects.show', [$project->id, 'lang' => 'en']))
            ->assertOk()->assertSee('Cancellation request sent')->assertDontSee('company/messages.');

        // Only one request at a time.
        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'other', 'note' => 'Another try at this.'])
            ->assertSessionHasErrors('reason');

        // Admin pages show it.
        $this->asAdmin()->get(route('admin.cancellations.index'))->assertOk()->assertSee('Waiting for decision')->assertSee('Red Sea Solar');
        $this->get(route('admin.cancellations.show', $request->id))->assertOk()->assertSee('roof cannot carry');

        // Approved without counting it.
        $this->post(route('admin.cancellations.approve', $request->id), ['counts_against_company' => 0, 'admin_note' => 'Checked with the customer.'])
            ->assertRedirect(route('admin.cancellations.show', $request->id));

        $request->refresh();
        $this->assertSame(ProjectCancellation::APPROVED, $request->status);
        $this->assertFalse($request->counts_against_company);
        $this->assertNotNull($request->decided_at);
        $this->assertNull(Project::query()->find($project->id));
        $this->assertSame('pending', $quote->fresh()->status);
        $this->assertSame('submitted', $lost->fresh()->status);
        $this->assertSame(0, ProjectCancellation::recentCountFor($this->company->id));

        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $won->id
            && $job->decision === CompanyOfferDecisionMail::CANCEL_APPROVED
            && $job->extra['adminNote'] === 'Checked with the customer.');

        Sanctum::actingAs($this->customer);
        $this->assertSame('offer_withdrawn', $this->getJson(self::API . '/notifications')->json('data.notifications.0.type'));

        // Deciding twice is refused.
        $this->asAdmin()->post(route('admin.cancellations.reject', $request->id), ['admin_note' => 'Too late now.'])->assertSessionHasErrors('status');
    }

    public function test_admin_rejects_and_the_job_carries_on(): void
    {
        [, $won, , $project] = $this->acceptedDeal();
        $project->forceFill(['status' => 'in_progress', 'started_at' => now()])->save();

        $this->asCompany()->post(route('company.projects.withdraw', $project->id), [
            'reason' => 'price_changed',
            'note' => 'The dollar rate doubled since the offer.',
        ]);
        $request = ProjectCancellation::query()->sole();

        $this->asAdmin()->post(route('admin.cancellations.reject', $request->id), ['admin_note' => ''])->assertSessionHasErrors('admin_note');
        $this->post(route('admin.cancellations.reject', $request->id), ['admin_note' => 'Please finish the job as agreed.'])->assertSessionHasNoErrors();

        $this->assertSame(ProjectCancellation::REJECTED, $request->fresh()->status);
        $this->assertSame('in_progress', $project->fresh()->status);
        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $won->id
            && $job->decision === CompanyOfferDecisionMail::CANCEL_REJECTED);

        $this->asCompany()->get(route('company.projects.show', [$project->id, 'lang' => 'en']))
            ->assertOk()->assertSee('Cancellation not approved')->assertSee('Please finish the job as agreed.');
    }

    public function test_company_takes_back_its_request(): void
    {
        [, , , $project] = $this->acceptedDeal();
        $project->forceFill(['status' => 'in_progress'])->save();

        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'no_capacity', 'note' => 'Our team is on another site.']);
        $this->asCompany()->post(route('company.projects.withdraw.cancel', $project->id))->assertSessionHasNoErrors();

        $this->assertSame(ProjectCancellation::CANCELLED, ProjectCancellation::query()->sole()->status);
        $this->asCompany()->post(route('company.projects.withdraw.cancel', $project->id))->assertSessionHasErrors('reason');
    }

    public function test_frequent_withdrawals_flag_the_company(): void
    {
        // 3 counted withdrawals in the window.
        for ($i = 0; $i < ProjectCancellation::FLAG_THRESHOLD; $i++) {
            [, , , $project] = $this->acceptedDeal();
            $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'no_capacity'])->assertSessionHasNoErrors();
        }

        $this->assertTrue(ProjectCancellation::isCompanyFlagged($this->company->id));
        $this->asCompany()->get(route('company.projects.index', ['lang' => 'en']))->assertOk()->assertSee('need approval from the SunXFinder team');

        // Listed after the other company in the app.
        $ids = collect($this->getJson(self::API . '/companies?per_page=50')->assertOk()->json('data.companies'))->pluck('id')->all();
        $this->assertLessThan(array_search($this->company->id, $ids), array_search($this->other->id, $ids));

        // The next withdrawal, even before starting, waits for an admin.
        [, , , $project] = $this->acceptedDeal();
        $this->asCompany()->post(route('company.projects.withdraw', $project->id), ['reason' => 'no_capacity', 'note' => 'Short on installers this month.'])
            ->assertRedirect(route('company.projects.show', $project->id));
        $this->assertSame(ProjectCancellation::REQUESTED, ProjectCancellation::query()->latest('id')->first()->status);
        $this->assertSame('accepted', $project->fresh()->status);

        // Waiving one clears the flag; old ones drop out after the window.
        $this->asAdmin()->post(route('admin.cancellations.count', ProjectCancellation::query()->oldest('id')->value('id')), ['counts_against_company' => 0])
            ->assertSessionHasNoErrors();
        $this->assertFalse(ProjectCancellation::isCompanyFlagged($this->company->id));
        $this->get(route('admin.companies.details', $this->company->id))->assertOk()->assertSee('Withdrawals from accepted jobs');

        $this->travel(ProjectCancellation::WINDOW_DAYS + 1)->days();
        $this->assertSame(0, ProjectCancellation::recentCountFor($this->company->id));
    }

    public function test_customer_accepts_a_new_price(): void
    {
        [$quote, $won, , $project] = $this->acceptedDeal();

        $this->asCompany()->post(route('company.projects.price-change', $project->id), [
            'new_price' => 1650000,
            'reason' => 'price_changed',
            'note' => 'The inverter price went up by 10%.',
        ])->assertSessionHasNoErrors();

        $change = OfferPriceChange::query()->sole();
        $this->assertSame(OfferPriceChange::PENDING, $change->status);

        // The installation cannot start until the customer answers.
        $this->asCompany()->post(route('company.projects.progress.update', $project->id), ['status' => 'in_progress'])
            ->assertSessionHasErrors('status');
        $this->asCompany()->get(route('company.projects.show', [$project->id, 'lang' => 'en']))
            ->assertOk()->assertSee('New price waiting for the customer')->assertDontSee('company/messages.');

        // The customer is asked, and sees it in My Quotes and project progress.
        Sanctum::actingAs($this->customer);
        $notification = $this->getJson(self::API . '/notifications')->json('data.notifications.0');
        $this->assertSame('offer_price_changed', $notification['type']);
        $this->assertStringContainsString('1,500,000 SDG', $notification['body']);
        $this->assertStringContainsString('1,650,000 SDG', $notification['body']);

        $myQuote = $this->myQuote($quote->id);
        $this->assertSame($change->id, $myQuote['price_change']['id']);
        $this->assertEquals(1650000, $myQuote['price_change']['new_price']);
        $this->assertEquals(10, $myQuote['price_change']['change_percent']);
        $this->getJson(self::API . '/project-progress/' . $project->id)
            ->assertOk()
            ->assertJsonPath('data.price_change.id', $change->id)
            ->assertJsonMissingPath('data.pending_price_change');

        $this->postJson(self::API . '/price-changes/' . $change->id . '/accept')
            ->assertOk()
            ->assertJsonPath('data.project_status', 'accepted');

        $won->refresh();
        $this->assertEquals(1650000, $won->price);
        $this->assertEquals(33000, $won->commission_amount);
        $this->assertSame(OfferPriceChange::ACCEPTED, $change->fresh()->status);
        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $won->id
            && $job->decision === CompanyOfferDecisionMail::PRICE_ACCEPTED);
        $this->assertNull($this->myQuote($quote->id)['price_change']);

        // Answering twice is refused; the company can now start.
        $this->postJson(self::API . '/price-changes/' . $change->id . '/decline')
            ->assertStatus(422)->assertJsonPath('reason', 'price_change_closed');
        $this->asCompany()->post(route('company.projects.progress.update', $project->id), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors();
        $this->assertSame('in_progress', $project->fresh()->status);
    }

    public function test_customer_declines_a_new_price_and_the_request_reopens(): void
    {
        [$quote, $won, $lost, $project] = $this->acceptedDeal();

        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 1900000, 'reason' => 'site_inspection']);
        $change = OfferPriceChange::query()->sole();

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/price-changes/' . $change->id . '/decline')
            ->assertOk()
            ->assertJsonPath('data.quote_status', 'pending')
            ->assertJsonPath('data.reopened_offers', 1);

        $this->assertSame(OfferPriceChange::DECLINED, $change->fresh()->status);
        $this->assertNull(Project::query()->find($project->id));
        $this->assertSame('withdrawn', $won->fresh()->status);
        $this->assertSame('submitted', $lost->fresh()->status);
        $this->assertEquals(1500000, $won->fresh()->price);

        // Recorded, but not counted against the company.
        $row = ProjectCancellation::query()->sole();
        $this->assertSame(ProjectCancellation::REASON_PRICE_DECLINED, $row->reason);
        $this->assertFalse($row->counts_against_company);

        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $won->id
            && $job->decision === CompanyOfferDecisionMail::PRICE_DECLINED);
        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $lost->id
            && $job->decision === CompanyOfferDecisionMail::REOPENED);

        $myQuote = $this->myQuote($quote->id);
        $this->assertSame('customer_declined_price', $myQuote['company_withdrawal']['reason']);
    }

    public function test_price_change_rules(): void
    {
        [, , , $project] = $this->acceptedDeal();
        $stranger = Customer::query()->forceCreate([
            'username' => 'stranger', 'password' => Hash::make('Secret1!'), 'full_name' => 'Stranger', 'phone' => '0922222222',
            'email' => 'stranger@example.test', 'city' => 'Khartoum', 'status' => 'active', 'is_active' => true,
        ]);

        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 1500000, 'reason' => 'price_changed'])
            ->assertSessionHasErrors('new_price');
        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 500, 'reason' => 'price_changed'])
            ->assertSessionHasErrors('new_price');
        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 1600000, 'reason' => 'other'])
            ->assertSessionHasErrors('note');

        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 1600000, 'reason' => 'price_changed'])
            ->assertSessionHasNoErrors();
        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 1700000, 'reason' => 'price_changed'])
            ->assertSessionHasErrors('new_price');

        $change = OfferPriceChange::query()->sole();
        Sanctum::actingAs($stranger);
        $this->postJson(self::API . '/price-changes/' . $change->id . '/accept')->assertNotFound();
        $this->postJson(self::API . '/price-changes/999999/accept')->assertNotFound();

        // Taken back by the company: nothing for the customer to answer.
        $this->asCompany()->post(route('company.projects.price-change.cancel', $project->id))->assertSessionHasNoErrors();
        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/price-changes/' . $change->id . '/accept')->assertStatus(422);

        // Not after the installation started.
        $project->forceFill(['status' => 'in_progress'])->save();
        $this->asCompany()->post(route('company.projects.price-change', $project->id), ['new_price' => 1600000, 'reason' => 'price_changed'])
            ->assertSessionHasErrors('new_price');
    }

    public function test_an_expired_offer_cannot_be_accepted(): void
    {
        $quote = Quote::query()->forceCreate([
            'customer_id' => $this->customer->id, 'quote_name' => 'Home', 'estimated_load' => '', 'peak_load' => '4 kW',
            'daily_load' => '20 kWh', 'status' => 'pending',
        ]);
        // Created directly, so pass the cast attribute through the model.
        $offer = $this->offer($quote, $this->company, 1500000);
        $offer->terms = ['validity_days' => 3];
        $offer->saveQuietly();
        $offer->refresh();

        $this->travel(4)->days();
        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/offer-accept', ['offer_id' => $offer->id, 'quote_id' => $quote->id, 'company_id' => $this->company->id])
            ->assertStatus(409)
            ->assertJsonPath('data.reason', 'offer_expired');
        $this->assertSame('pending', $quote->fresh()->status);

        // The last valid day still works.
        $this->travel(-1)->days();
        $this->postJson(self::API . '/offer-accept', ['offer_id' => $offer->id, 'quote_id' => $quote->id, 'company_id' => $this->company->id])
            ->assertOk();
    }

    public function test_deal_change_emails_render_for_each_case(): void
    {
        $details = ['companyName' => 'Red Sea Solar', 'customerName' => 'Mohamed', 'quote_id' => 12, 'quoteName' => 'Home',
            'price' => '1,500,000 SDG', 'actionUrl' => 'https://example.test/q/12'];

        $render = fn(string $decision, array $extra = []) => (new CompanyOfferDecisionMail($decision, $details + $extra))->render();

        $this->assertStringContainsString('open again', $render(CompanyOfferDecisionMail::REOPENED));
        $this->assertStringContainsString('accepted your new price', $render(CompanyOfferDecisionMail::PRICE_ACCEPTED, ['oldPrice' => '1,500,000 SDG', 'newPrice' => '1,650,000 SDG']));
        $this->assertStringContainsString('1,650,000 SDG', $render(CompanyOfferDecisionMail::PRICE_DECLINED, ['newPrice' => '1,650,000 SDG']));
        $this->assertStringContainsString('approved your request', $render(CompanyOfferDecisionMail::CANCEL_APPROVED, ['reasonText' => 'Out of stock']));
        $rejected = $render(CompanyOfferDecisionMail::CANCEL_REJECTED, ['adminNote' => 'Finish the job please']);
        $this->assertStringContainsString('did not approve', $rejected);
        $this->assertStringContainsString('Finish the job please', $rejected);
    }

    public function test_company_project_page_renders_in_arabic(): void
    {
        [, , , $project] = $this->acceptedDeal();

        // The company portal opens in Arabic by default.
        $this->asCompany()->get(route('company.projects.show', $project->id))
            ->assertOk()
            ->assertSee('هل هناك مشكلة في هذا العمل؟')
            ->assertSee('الانسحاب من هذا العمل')
            ->assertDontSee('company/messages.');
        $this->asCompany()->get(route('company.projects.index'))->assertOk()->assertDontSee('company/messages.');
    }
}
