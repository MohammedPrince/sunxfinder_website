<?php

namespace Tests\Feature;

use App\Mail\CompanyProjectStalledMail;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Support\WhatsApp;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Admin "Email company" on stalled projects (admin/projects?stalled=1).
 * Run with: vendor/bin/phpunit -c phpunit.mysql.xml
 */
class StalledProjectReminderTest extends TestCase
{
    use RefreshDatabase;

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

        Mail::fake();
        Cache::flush();
        $this->withoutMiddleware(ValidateCsrfToken::class);

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
    }

    private function makeCompany(string $name, string $slug, ?string $email = null): SolarCompany
    {
        return SolarCompany::query()->forceCreate([
            'name' => $name,
            'slug' => $slug,
            'username' => $slug,
            'email' => $email ?? $slug . '@example.test',
            'phone' => '+2499000' . random_int(10000, 99999),
            'city' => 'Khartoum',
            'license_number' => 'LIC-' . $slug,
            'password' => Hash::make('secret123'),
            'status' => 'approved',
            'is_verified' => true,
            'is_active' => true,
            'commission_rate' => 2,
        ]);
    }

    /** A project for this company whose last activity was $idleDays ago. */
    private function project(SolarCompany $company, int $idleDays, string $status = 'in_progress'): Project
    {
        $this->travelTo(now()->subDays($idleDays));

        $quote = Quote::query()->forceCreate([
            'customer_id' => $this->customer->id,
            'company_id' => $company->id,
            'quote_name' => 'Home system',
            'status' => 'accepted',
        ]);
        $offer = CompanyQuotation::query()->forceCreate([
            'quote_id' => $quote->id,
            'company_id' => $company->id,
            'customer_id' => $this->customer->id,
            'price' => 1500000,
            'currency' => 'SDG',
            'status' => 'selected',
        ]);
        $project = Project::query()->forceCreate([
            'quote_id' => $quote->id,
            'company_quotation_id' => $offer->id,
            'customer_id' => $this->customer->id,
            'company_id' => $company->id,
            'status' => $status,
            'progress_percent' => 40,
            'accepted_at' => now(),
        ]);

        $this->travelBack();

        return $project;
    }

    private function asAdmin(): static
    {
        $admin = Admin::query()->where('username', 'admin')->first()
            ?? Admin::query()->forceCreate(['username' => 'admin', 'password' => Hash::make('secret123'), 'is_active' => true]);

        return $this->actingAs($admin, 'admin');
    }

    public function test_stalled_list_shows_email_buttons(): void
    {
        $project = $this->project($this->makeCompany('Red Sea Solar', 'redsea'), 20);

        $this->asAdmin()
            ->get(route('admin.projects.index', ['stalled' => 1]))
            ->assertOk()
            ->assertSee('Email all stalled companies')
            ->assertSee(route('admin.projects.remind', $project->id), false);

        $this->get(route('admin.projects.show', $project->id))
            ->assertOk()
            ->assertSee('Remind the company')
            ->assertSee('redsea@example.test');
    }

    public function test_admin_emails_the_company_of_one_stalled_project_with_a_note(): void
    {
        $company = $this->makeCompany('Red Sea Solar', 'redsea');
        $project = $this->project($company, 20);

        $this->asAdmin()
            ->from(route('admin.projects.show', $project->id))
            ->post(route('admin.projects.remind', $project->id), ['note' => 'يرجى إرسال موعد التركيب'])
            ->assertRedirect(route('admin.projects.show', $project->id))
            ->assertSessionHas('success', 'Reminder emailed to 1 company.');

        Mail::assertQueued(CompanyProjectStalledMail::class, function (CompanyProjectStalledMail $mail) use ($project) {
            return $mail->hasTo('redsea@example.test')
                && $mail->details['projectId'] === $project->id
                && $mail->details['idleDays'] === 20
                && $mail->details['note'] === 'يرجى إرسال موعد التركيب';
        });

        $this->assertTrue(ActivityLog::query()->where('action', 'nudged')->where('description', 'like', '%stalled project #' . $project->id . '%')->exists());

        // Both languages are in the one email.
        $html = (new CompanyProjectStalledMail(Mail::queued(CompanyProjectStalledMail::class)->first()->details))->render();
        $this->assertStringContainsString('Your project needs', $html);
        $this->assertStringContainsString('يحتاج إلى تحديث', $html);
        $this->assertStringContainsString('يرجى إرسال موعد التركيب', $html);
        $this->assertStringContainsString(route('company.projects.show', $project->id), $html);
    }

    public function test_the_same_project_is_not_emailed_twice_within_the_cooldown(): void
    {
        $project = $this->project($this->makeCompany('Red Sea Solar', 'redsea'), 20);

        $this->asAdmin()->post(route('admin.projects.remind', $project->id))->assertSessionHas('success');
        $this->post(route('admin.projects.remind', $project->id))->assertSessionHas('error');

        Mail::assertQueuedCount(1);

        $this->get(route('admin.projects.index', ['stalled' => 1]))->assertSee('Company emailed');

        $this->travel(25)->hours();
        $this->post(route('admin.projects.remind', $project->id))->assertSessionHas('success');
        Mail::assertQueuedCount(2);
    }

    public function test_projects_that_are_not_stalled_are_not_emailed(): void
    {
        $company = $this->makeCompany('Red Sea Solar', 'redsea');
        $recent = $this->project($company, 3);
        $done = $this->project($company, 30, 'completed');

        $this->asAdmin()->post(route('admin.projects.remind', $recent->id))->assertSessionHas('error');
        $this->post(route('admin.projects.remind', $done->id))->assertSessionHas('error');

        Mail::assertNothingQueued();
    }

    public function test_whatsapp_numbers_are_normalised_to_the_international_form(): void
    {
        $this->assertSame('249919046917', WhatsApp::number('+249 91 904 6917'));
        $this->assertSame('249919046917', WhatsApp::number('00249919046917'));
        $this->assertSame('249919046917', WhatsApp::number('0919046917'));
        $this->assertSame('249919046917', WhatsApp::number('919046917'));
        $this->assertNull(WhatsApp::number('12345'));
        $this->assertNull(WhatsApp::number(null));
    }

    public function test_whatsapp_opens_a_chat_with_the_company_and_the_reminder_filled_in(): void
    {
        config(['services.whatsapp.phone_number' => '+249919046917']);
        $company = $this->makeCompany('Red Sea Solar', 'redsea');
        $company->forceFill(['phone' => '0912 345 678'])->save();
        $project = $this->project($company, 20);

        $this->asAdmin()
            ->get(route('admin.projects.index', ['stalled' => 1]))
            ->assertSee(route('admin.projects.whatsapp', $project->id), false);

        $this->get(route('admin.projects.show', $project->id))
            ->assertSee('WhatsApp +249912345678');

        $response = $this->get(route('admin.projects.whatsapp', ['id' => $project->id, 'note' => 'Please call the customer']));
        $location = $response->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://wa.me/249912345678?text=', $location);
        $text = rawurldecode(substr($location, strpos($location, '?text=') + 6));
        $this->assertStringContainsString('لم يُسجَّل أي تحديث على المشروع رقم ' . $project->id, $text);
        $this->assertStringContainsString('has had no update for 20 days', $text);
        $this->assertStringContainsString(route('company.projects.show', $project->id), $text);
        $this->assertStringContainsString('Please call the customer', $text);
        $this->assertStringContainsString('+249 91 904 6917', $text);

        $this->assertTrue(ActivityLog::query()->where('action', 'whatsapp_opened')->where('subject_id', $project->id)->exists());
    }

    public function test_whatsapp_link_from_the_company_profile_wins_over_its_phone(): void
    {
        $company = $this->makeCompany('Red Sea Solar', 'redsea');
        $company->forceFill(['phone' => '0912345678', 'social_media' => json_encode(['whatsapp' => 'https://wa.me/249900000001'])])->save();

        $this->assertSame('249900000001', WhatsApp::companyNumber($company->fresh()));
    }

    public function test_whatsapp_is_refused_for_a_project_that_is_not_stalled(): void
    {
        $project = $this->project($this->makeCompany('Red Sea Solar', 'redsea'), 2);

        $this->asAdmin()
            ->get(route('admin.projects.whatsapp', $project->id))
            ->assertRedirect(route('admin.projects.show', $project->id))
            ->assertSessionHas('error');
    }

    public function test_email_all_reminds_every_stalled_company_and_skips_the_rest(): void
    {
        $redSea = $this->makeCompany('Red Sea Solar', 'redsea');
        $nile = $this->makeCompany('Nile Power', 'nile');
        $noEmail = $this->makeCompany('Kassala Sun', 'kassala', '');

        $this->project($redSea, 20);
        $this->project($nile, 15, 'accepted');
        $this->project($noEmail, 40);
        $this->project($nile, 2);

        $this->asAdmin()
            ->post(route('admin.projects.remind-stalled'))
            ->assertSessionHas('success', 'Reminder emailed to 2 companies. Skipped: 1 with no company email.');

        Mail::assertQueuedCount(2);
        Mail::assertQueued(CompanyProjectStalledMail::class, fn($mail) => $mail->hasTo('redsea@example.test'));
        Mail::assertQueued(CompanyProjectStalledMail::class, fn($mail) => $mail->hasTo('nile@example.test'));

        // Pressing again right away sends nothing new.
        $this->post(route('admin.projects.remind-stalled'))->assertSessionHas('error');
        Mail::assertQueuedCount(2);
    }
}
