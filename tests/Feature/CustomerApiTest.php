<?php

namespace Tests\Feature;

use App\Jobs\SendCompanyOfferDecisionEmail;
use App\Mail\CompanyOfferDecisionMail;
use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerComplaint;
use App\Models\CustomerDeviceToken;
use App\Models\CustomerNotification;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\SolarCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Contract\Messaging;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The customer app routes (routes/api.php, prefix /api/v1/customer).
 * Each route: success, no token (401), validation (422), someone else's record (404).
 * Run with: vendor/bin/phpunit -c phpunit.mysql.xml
 */
class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/customer';

    private SolarCompany $company;
    private Customer $customer;
    private Customer $stranger;

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
            'on_app' => true,
        ]);

        $this->customer = $this->makeCustomer('mohamed', '0911111111');
        $this->stranger = $this->makeCustomer('stranger', '0922222222');
    }

    private function makeCustomer(string $username, string $phone, array $overrides = []): Customer
    {
        $customer = Customer::query()->forceCreate(array_merge([
            'username' => $username,
            'password' => Hash::make('Secret1!'),
            'full_name' => ucfirst($username) . ' Ahmed',
            'phone' => $phone,
            'email' => $username . '@example.test',
            'city' => 'Khartoum',
            'status' => 'active',
            'is_active' => true,
        ], $overrides));

        CustomerDeviceToken::query()->create([
            'customer_id' => $customer->id,
            'fcm_token' => 'token-' . $username,
            'token_hash' => hash('sha256', 'token-' . $username),
            'platform' => 'android',
            'lang' => 'en',
            'is_active' => true,
        ]);

        return $customer;
    }

    private function makeQuote(Customer $customer, string $status = 'pending'): Quote
    {
        return Quote::query()->forceCreate([
            'customer_id' => $customer->id,
            'quote_name' => 'Home system',
            'estimated_load' => 'Estimated load: 20 kWh/day',
            'peak_load' => '4.2 kW',
            'daily_load' => '20 kWh',
            'status' => $status,
        ]);
    }

    private function makeOffer(Quote $quote, string $status = 'submitted'): CompanyQuotation
    {
        return CompanyQuotation::query()->forceCreate([
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
            'customer_id' => $quote->customer_id,
            'price' => 1500000,
            'currency' => 'SDG',
            'status' => $status,
        ]);
    }

    /** A quote whose offer the customer accepted through the API: project "accepted", 5 milestones. */
    private function acceptedQuote(Customer $customer): array
    {
        $quote = $this->makeQuote($customer);
        $offer = $this->makeOffer($quote);

        Sanctum::actingAs($customer);
        $this->postJson(self::API . '/offer-accept', [
            'offer_id' => $offer->id,
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
        ])->assertOk();

        return [$quote->fresh(), $offer->fresh(), Project::query()->where('quote_id', $quote->id)->sole()];
    }

    /** What the company does in its panel: start installation and finish every step but the handover. */
    private function finishInstallation(Project $project): void
    {
        $project->forceFill(['status' => 'in_progress', 'started_at' => now(), 'progress_percent' => 99])->save();

        ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->whereRaw('LOWER(title) <> ?', ['customer handover'])
            ->update(['status' => 'completed', 'completed_at' => now()]);
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'new_customer',
            'first_name' => 'Amna',
            'phone' => '0933333333',
            'email' => 'amna@example.test',
            'city' => 'Omdurman',
            'password' => 'Secret1!',
            'lang' => 'en',
        ], $overrides);
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Mohamed',
            'phone' => '0911111111',
            'email' => 'mohamed@example.test',
            'city' => 'Khartoum',
        ], $overrides);
    }

    // ---- Sign-up and profile: name, phone and city required; email and second name optional ----

    public function test_sign_up_with_name_phone_email_and_city_is_created(): void
    {
        $this->postJson(self::API . '/register', $this->registerPayload())
            ->assertCreated()
            ->assertJsonPath('data.account.email', 'amna@example.test')
            ->assertJsonPath('data.account.city', 'Omdurman');
    }

    public function test_sign_up_needs_an_email_and_a_city(): void
    {
        $this->postJson(self::API . '/register', $this->registerPayload(['email' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->postJson(self::API . '/register', $this->registerPayload(['city' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('city');

        $this->assertFalse(Customer::query()->where('username', 'new_customer')->exists());
    }

    public function test_customer_without_second_name_updates_their_phone(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson(self::API . '/profile', $this->profilePayload(['phone' => '0955555555']))
            ->assertOk()
            ->assertJsonPath('data.phone', '0955555555')
            ->assertJsonPath('data.second_name', '')
            ->assertJsonPath('data.email', 'mohamed@example.test');

        $this->assertSame('0955555555', $this->customer->fresh()->phone);
    }

    public function test_profile_needs_an_email_a_city_and_a_free_phone(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson(self::API . '/profile', $this->profilePayload(['city' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('city');

        $this->postJson(self::API . '/profile', $this->profilePayload(['email' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        // The stranger's phone: a clear 422, not a database error.
        $this->postJson(self::API . '/profile', $this->profilePayload(['phone' => '0922222222']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');

        $this->postJson(self::API . '/profile', $this->profilePayload(['email' => 'stranger@example.test']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_profile_needs_a_token(): void
    {
        $this->getJson(self::API . '/profile')->assertUnauthorized();
        $this->postJson(self::API . '/profile', $this->profilePayload())->assertUnauthorized();
    }

    public function test_customer_without_email_signs_in_with_the_username(): void
    {
        $this->customer->forceFill(['email' => null])->save();

        $this->postJson(self::API . '/login', ['username' => 'mohamed', 'password' => 'Secret1!'])
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_forgot_password_without_an_email_explains_why(): void
    {
        $this->customer->forceFill(['email' => null])->save();

        // No email on the account: contact support.
        $this->postJson(self::API . '/password/forgot', ['phone' => '0911111111', 'lang' => 'en'])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'no_email')
            ->assertJsonPath('error', 'No email on this account, so we cannot send a reset code. Please contact support.');

        // The account has an email: the customer must type it.
        $this->postJson(self::API . '/password/forgot', ['phone' => '0922222222', 'lang' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'email_required');

        $this->postJson(self::API . '/password/forgot', ['phone' => '0999999999'])
            ->assertNotFound()
            ->assertJsonPath('reason', 'account_not_found');
    }

    // ---- Quote requests ----

    public function test_customer_sends_a_quote_request(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson(self::API . '/quote', [
            'company_id' => null,
            'quote_name' => 'Home system',
            'estimated_load' => 'Estimated load: 20 kWh/day',
            'peak_load' => '4.2 kW',
            'daily_load' => '20 kWh',
            'message' => null,
        ])->assertCreated()->assertJsonPath('status', 'success');

        $this->assertSame(1, Quote::query()->where('customer_id', $this->customer->id)->count());
    }

    public function test_customer_without_email_sends_a_quote_request(): void
    {
        $this->customer->forceFill(['email' => null])->save();
        Sanctum::actingAs($this->customer);

        $this->postJson(self::API . '/quote', [
            'company_id' => $this->company->id,
            'quote_name' => 'Home system',
            'estimated_load' => 'Estimated load: 20 kWh/day',
            'peak_load' => '4.2 kW',
            'daily_load' => '20 kWh',
            'message' => null,
        ])->assertCreated();

        $this->assertSame($this->company->id, (int) Quote::query()->where('customer_id', $this->customer->id)->value('company_id'));
    }

    public function test_quote_request_validation_and_token(): void
    {
        $this->postJson(self::API . '/quote', [])->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/quote', ['quote_name' => 'Home system'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['peak_load', 'daily_load']);
    }

    public function test_my_quotes_lists_only_my_requests(): void
    {
        $mine = $this->makeQuote($this->customer);
        $this->makeQuote($this->stranger);

        $this->postJson(self::API . '/my-quotes')->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $ids = collect($this->postJson(self::API . '/my-quotes')->assertOk()->json('data.quotes'))->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    // ---- Offers ----

    public function test_customer_accepts_an_offer_and_a_project_opens(): void
    {
        [$quote, $offer, $project] = $this->acceptedQuote($this->customer);

        $this->assertSame('accepted', $quote->status);
        $this->assertSame('selected', $offer->status);
        $this->assertSame('accepted', $project->status);
        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->decision === CompanyOfferDecisionMail::ACCEPTED);
    }

    public function test_offer_accept_validation_token_and_someone_elses_quote(): void
    {
        $quote = $this->makeQuote($this->stranger);
        $offer = $this->makeOffer($quote);
        $payload = ['offer_id' => $offer->id, 'quote_id' => $quote->id, 'company_id' => $this->company->id];

        $this->postJson(self::API . '/offer-accept', $payload)->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/offer-accept', ['quote_id' => $quote->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['offer_id', 'company_id']);
        $this->postJson(self::API . '/offer-accept', $payload)->assertNotFound();

        $this->assertSame('submitted', $offer->fresh()->status);
    }

    public function test_customer_rejects_an_offer(): void
    {
        $quote = $this->makeQuote($this->customer);
        $offer = $this->makeOffer($quote);
        $payload = ['offer_id' => $offer->id, 'quote_id' => $quote->id, 'company_id' => $this->company->id];

        $this->postJson(self::API . '/offer-reject', $payload)->assertUnauthorized();

        // Someone else cannot reject it.
        Sanctum::actingAs($this->stranger);
        $this->postJson(self::API . '/offer-reject', $payload)->assertNotFound();

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/offer-reject', ['offer_id' => $offer->id])->assertStatus(422);
        $this->postJson(self::API . '/offer-reject', $payload)->assertOk();

        $this->assertSame('withdrawn', $offer->fresh()->status);
        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->decision === CompanyOfferDecisionMail::REJECTED);
    }

    // ---- POST /quote-complete: the customer confirms the handover ----

    public function test_customer_confirms_the_handover_and_the_project_completes(): void
    {
        [$quote, $offer, $project] = $this->acceptedQuote($this->customer);
        $this->finishInstallation($project);
        $projectsBefore = (int) $this->company->fresh()->number_of_projects;

        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])
            ->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'message' => 'Marked as completed.',
                'data' => ['quote_id' => $quote->id, 'quote_status' => 'completed'],
            ]);

        $project->refresh();
        $this->assertSame('completed', $project->status);
        $this->assertSame(100, (int) $project->progress_percent);
        $this->assertNotNull($project->completed_at);
        $this->assertSame('completed', $quote->fresh()->status);
        $this->assertSame(0, $project->milestones()->where('status', '!=', 'completed')->count());
        $this->assertSame($projectsBefore + 1, (int) $this->company->fresh()->number_of_projects);
        $this->assertSame('customer', $project->updates()->latest('id')->value('updated_by_type'));

        // The company is emailed.
        Bus::assertDispatched(SendCompanyOfferDecisionEmail::class, fn($job) => $job->offerId === $offer->id
            && $job->decision === CompanyOfferDecisionMail::COMPLETED);

        // My Quotes shows it completed, and the customer can rate the company.
        $myQuote = collect($this->postJson(self::API . '/my-quotes')->json('data.quotes'))->firstWhere('id', $quote->id);
        $this->assertSame('completed', $myQuote['status']);

        $this->postJson(self::API . '/rating', [
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
            'stars' => 5,
        ])->assertCreated();
    }

    public function test_confirming_twice_returns_200_both_times(): void
    {
        [$quote, , $project] = $this->acceptedQuote($this->customer);
        $this->finishInstallation($project);

        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])->assertOk();
        $projects = (int) $this->company->fresh()->number_of_projects;

        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])
            ->assertOk()
            ->assertJsonPath('data.quote_status', 'completed');

        // Counted once, emailed once.
        $this->assertSame($projects, (int) $this->company->fresh()->number_of_projects);
        Bus::assertDispatchedTimes(SendCompanyOfferDecisionEmail::class, 2); // accept + completed
    }

    public function test_a_project_the_company_already_completed_returns_200(): void
    {
        [$quote, , $project] = $this->acceptedQuote($this->customer);
        $project->forceFill(['status' => 'completed', 'progress_percent' => 100, 'completed_at' => now()])->save();

        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])
            ->assertOk()
            ->assertJsonPath('data.quote_status', 'completed');

        $this->assertSame('completed', $quote->fresh()->status);
    }

    public function test_company_completing_the_handover_also_completes_the_quote(): void
    {
        [$quote, , $project] = $this->acceptedQuote($this->customer);
        $this->finishInstallation($project);

        $milestones = $project->milestones()->get()->mapWithKeys(fn($milestone) => [
            $milestone->id => ['status' => 'completed'],
        ])->all();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->actingAs($this->company, 'company')
            ->post(route('company.projects.progress.update', $project->id), [
                'status' => 'completed',
                'milestones' => $milestones,
            ])->assertSessionHasNoErrors();

        $this->assertSame('completed', $project->fresh()->status);
        $this->assertSame('completed', $quote->fresh()->status);
    }

    public function test_handover_cannot_be_confirmed_before_installation_is_done(): void
    {
        [$quote, , $project] = $this->acceptedQuote($this->customer);

        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'installation_not_finished')
            ->assertJsonValidationErrors('quote_id');

        $this->assertSame('accepted', $project->fresh()->status);
        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_quote_without_an_accepted_offer_cannot_be_completed(): void
    {
        $quote = $this->makeQuote($this->customer);
        $this->makeOffer($quote);

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'no_accepted_offer')
            ->assertJsonValidationErrors('quote_id');
    }

    public function test_quote_complete_token_validation_and_someone_elses_quote(): void
    {
        $this->postJson(self::API . '/quote-complete', ['quote_id' => 1])->assertUnauthorized();

        [$quote, , $project] = $this->acceptedQuote($this->customer);
        $this->finishInstallation($project);

        Sanctum::actingAs($this->stranger);
        $this->postJson(self::API . '/quote-complete', ['quote_id' => $quote->id])->assertNotFound();
        $this->postJson(self::API . '/quote-complete', ['quote_id' => 999999])->assertNotFound();
        $this->postJson(self::API . '/quote-complete', [])->assertStatus(422)->assertJsonValidationErrors('quote_id');

        $this->assertSame('in_progress', $project->fresh()->status);
    }

    // ---- Ratings ----

    public function test_rating_create_update_and_delete(): void
    {
        [$quote] = $this->acceptedQuote($this->customer);

        $this->postJson(self::API . '/rating', ['quote_id' => $quote->id, 'company_id' => $this->company->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('stars');

        $ratingId = $this->postJson(self::API . '/rating', [
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
            'stars' => 4,
            'comment' => 'Fast work.',
        ])->assertCreated()->json('data.ratingDetails.id');

        $this->patchJson(self::API . '/rating/' . $ratingId, ['stars' => 0])->assertStatus(422);
        $this->patchJson(self::API . '/rating/' . $ratingId, ['stars' => 5])->assertOk();
        $this->assertSame(5, (int) Rating::query()->findOrFail($ratingId)->stars);

        // Someone else's rating is not found.
        Sanctum::actingAs($this->stranger);
        $this->patchJson(self::API . '/rating/' . $ratingId, ['stars' => 1])->assertNotFound();
        $this->deleteJson(self::API . '/rating/' . $ratingId)->assertNotFound();

        Sanctum::actingAs($this->customer);
        $this->deleteJson(self::API . '/rating/' . $ratingId)->assertOk();
        $this->assertNull(Rating::query()->find($ratingId));
    }

    public function test_rating_someone_elses_quote_is_not_found(): void
    {
        [$quote] = $this->acceptedQuote($this->customer);

        Sanctum::actingAs($this->stranger);
        $this->postJson(self::API . '/rating', [
            'quote_id' => $quote->id,
            'company_id' => $this->company->id,
            'stars' => 1,
        ])->assertNotFound();
    }

    public function test_rating_needs_a_token(): void
    {
        $this->postJson(self::API . '/rating', [])->assertUnauthorized();
        $this->patchJson(self::API . '/rating/1', ['stars' => 3])->assertUnauthorized();
        $this->deleteJson(self::API . '/rating/1')->assertUnauthorized();
    }

    // ---- Delete account ----

    public function test_delete_account_is_refused_while_a_project_is_unfinished(): void
    {
        $this->acceptedQuote($this->customer);

        $this->postJson(self::API . '/delete-account')
            ->assertStatus(409)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('reason', 'active_project')
            ->assertJsonPath('message', 'Your account cannot be deleted while you have an active project. Please wait until the project is completed.');

        $this->assertFalse($this->customer->fresh()->trashed());
    }

    public function test_delete_account_message_follows_the_app_language(): void
    {
        $this->acceptedQuote($this->customer);
        CustomerDeviceToken::query()->where('customer_id', $this->customer->id)->update(['lang' => 'ar']);

        $this->postJson(self::API . '/delete-account')
            ->assertStatus(409)
            ->assertJsonPath('message', 'لا يمكن حذف حسابك لوجود مشروع نشط. يُرجى الانتظار حتى اكتمال المشروع.');
    }

    public function test_delete_account_signs_out_and_removes_the_account(): void
    {
        $this->makeQuote($this->customer);
        $this->customer->createToken('app');

        $this->postJson(self::API . '/delete-account')->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/delete-account')->assertOk()->assertJsonPath('status', 'success');

        $customer = Customer::withTrashed()->findOrFail($this->customer->id);
        $this->assertTrue($customer->trashed());
        $this->assertSame('deleted', $customer->status);
        $this->assertSame(0, $customer->tokens()->count());
        $this->assertSame(0, CustomerDeviceToken::query()->where('customer_id', $customer->id)->where('is_active', true)->count());
        $this->assertSame(0, Quote::query()->where('customer_id', $customer->id)->count());
    }

    // ---- Notifications ----

    private function notify(Customer $customer, string $title): Notification
    {
        $notification = Notification::query()->forceCreate([
            'type' => 'general',
            'audience_type' => 'selected',
            'title_en' => $title,
            'message_en' => $title . ' body',
            'status' => 'published',
            'sender_type' => 'admin',
        ]);

        CustomerNotification::query()->forceCreate([
            'notification_id' => $notification->id,
            'customer_id' => $customer->id,
        ]);

        return $notification;
    }

    public function test_notifications_list_and_read_only_my_own(): void
    {
        $mine = $this->notify($this->customer, 'For me');
        $theirs = $this->notify($this->stranger, 'For stranger');

        $this->getJson(self::API . '/notifications')->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $ids = collect($this->getJson(self::API . '/notifications')->assertOk()->json('data.notifications'))->pluck('id')->all();
        $this->assertSame([$mine->id], $ids);

        $this->getJson(self::API . '/notifications/' . $theirs->id)->assertNotFound();
        $this->postJson(self::API . '/notifications/' . $theirs->id . '/read')->assertNotFound();

        $this->postJson(self::API . '/notifications/' . $mine->id . '/read')->assertOk();
        $this->assertNotNull(CustomerNotification::query()->where('notification_id', $mine->id)->value('read_at'));
    }

    // ---- Complaints ----

    public function test_customer_files_and_lists_complaints(): void
    {
        $this->postJson(self::API . '/complaints', [])->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $this->postJson(self::API . '/complaints', ['company_id' => $this->company->id, 'description' => 'Late.'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
        $this->postJson(self::API . '/complaints', ['company_id' => 999999, 'category' => 'other', 'description' => 'Late.'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('company_id');

        $this->postJson(self::API . '/complaints', [
            'company_id' => $this->company->id,
            'category' => 'delivery_issue',
            'description' => 'The panels arrived late.',
        ])->assertCreated();

        CustomerComplaint::query()->create([
            'customer_id' => $this->stranger->id,
            'company_id' => $this->company->id,
            'category' => 'other',
            'description' => 'Not mine.',
            'status' => 'pending',
        ]);

        $complaints = $this->getJson(self::API . '/complaints')->assertOk()->json('data.complaints');
        $this->assertCount(1, $complaints);
        $this->assertSame('The panels arrived late.', $complaints[0]['description']);
    }

    // ---- Company contact details in the app (Admin → Companies → Edit) ----

    public function test_company_page_shows_email_and_phone_by_default(): void
    {
        Sanctum::actingAs($this->customer);

        $this->getJson(self::API . '/companies/' . $this->company->id)
            ->assertOk()
            ->assertJsonPath('data.company.email', 'redsea@example.test')
            ->assertJsonPath('data.company.phone', '+249900000002')
            ->assertJsonPath('data.company.show_email', true)
            ->assertJsonPath('data.company.show_phone', true);
    }

    public function test_admin_hides_the_company_phone_and_email_from_the_app(): void
    {
        $this->company->forceFill(['social_media' => json_encode([
            'whatsapp' => 'https://wa.me/249900000002',
            'facebook' => 'https://facebook.com/redsea',
        ])])->save();

        $admin = \App\Models\Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->put(route('admin.companies.update', $this->company->id), [
                'name' => 'Red Sea Solar',
                'username' => 'redsea',
                'email' => 'redsea@example.test',
                'phone' => '+249900000002',
                'city' => 'Khartoum',
                'license_number' => 'LIC-redsea',
                'services' => 'Installation',
                'ar_services' => 'تركيب',
                'status' => 'approved',
                'is_verified' => '1',
                'is_active' => '1',
                'year_of_exp' => '3',
                'number_of_projects' => '5',
                'commission_rate' => '0',
                'show_email_in_app' => '0',
                'show_phone_in_app' => '0',
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.companies.index'));

        $this->assertFalse($this->company->fresh()->show_phone_in_app);
        $this->assertFalse($this->company->fresh()->show_email_in_app);

        // The directory shows the phone, marked as hidden in the app.
        $this->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee('<th>Phone</th>', false)
            ->assertSee('+249900000002')
            ->assertSee('Hidden in app');
        $this->get(route('admin.companies.edit', $this->company->id))
            ->assertOk()
            ->assertSee('name="show_phone_in_app"', false);

        Sanctum::actingAs($this->customer);
        $this->getJson(self::API . '/companies/' . $this->company->id)
            ->assertOk()
            ->assertJsonPath('data.company.email', null)
            ->assertJsonPath('data.company.phone', null)
            ->assertJsonPath('data.company.show_email', false)
            ->assertJsonPath('data.company.show_phone', false)
            // WhatsApp is a phone number: hidden with the phone. Other links stay.
            ->assertJsonPath('data.company.social_links.whatsapp', null)
            ->assertJsonPath('data.company.social_links.facebook', 'https://facebook.com/redsea');
    }

    // ---- Pending load from the website ----

    public function test_pending_load_is_read_and_cleared(): void
    {
        $this->getJson(self::API . '/pending-load')->assertUnauthorized();

        Sanctum::actingAs($this->customer);
        $this->getJson(self::API . '/pending-load')->assertOk()->assertJsonPath('data.pending_load', null);
        $this->deleteJson(self::API . '/pending-load', ['id' => str_repeat('x', 65)])->assertStatus(422);
        $this->deleteJson(self::API . '/pending-load')->assertOk();
    }
}
