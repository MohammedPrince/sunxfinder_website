<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/** Admin → System → Server Tools (hosting only). */
class AdminServerToolsTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

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
        $this->actingAs(Admin::query()->forceCreate([
            'username' => 'admin',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]), 'admin');

        $this->tmp = storage_path('framework/testing/server-tools-' . uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    /** Pretend this is the hosting, as PROD (sunxfinder_prod) or DEV (sunXfinder). */
    private function onHosting(string $site = 'PROD'): void
    {
        config([
            'server_tools.enabled' => true,
            'server_tools.php' => '/opt/cpanel/ea-php82/root/usr/bin/php',
            // The test project folder stands in for public_html/Repository/sunxfinder_prod or /sunXfinder.
            'server_tools.sites' => [basename(base_path()) => $site === 'PROD'
                ? ['name' => 'PROD', 'url' => 'https://sunxfinder.com', 'copy_public' => true]
                : ['name' => 'DEV', 'url' => 'https://dev.sunxfinder.com', 'copy_public' => false]],
        ]);
    }

    public function test_page_shows_which_site_it_is(): void
    {
        $this->onHosting('DEV');
        $this->get(route('admin.server-tools.index'))
            ->assertOk()
            ->assertSee('https://dev.sunxfinder.com')
            ->assertSee('Off on DEV: only PROD copies to public_html.');

        $this->onHosting('PROD');
        $this->get(route('admin.server-tools.index'))
            ->assertOk()
            ->assertSee('https://sunxfinder.com')
            ->assertDontSee('Off on PROD');
    }

    public function test_the_default_sites_match_the_hosting_folders(): void
    {
        $this->assertSame('PROD', config('server_tools.sites.sunxfinder_prod.name'));
        $this->assertTrue(config('server_tools.sites.sunxfinder_prod.copy_public'));
        $this->assertSame('DEV', config('server_tools.sites.sunXfinder.name'));
        $this->assertFalse(config('server_tools.sites.sunXfinder.copy_public'));
    }

    public function test_dev_never_copies_public_folders(): void
    {
        $this->onHosting('DEV');
        File::ensureDirectoryExists($this->tmp . '/project/public/css');
        File::ensureDirectoryExists($this->tmp . '/public_html');
        config([
            'server_tools.public_source' => $this->tmp . '/project/public',
            'server_tools.public_target' => $this->tmp . '/public_html',
        ]);

        $this->post(route('admin.server-tools.run'), ['action' => 'copy_public'])
            ->assertSessionHas('error')
            ->assertSessionHas('tool_output', fn($output) => str_contains($output, 'PROD only'));

        $this->assertFalse(File::isDirectory($this->tmp . '/public_html/css'));
    }

    public function test_config_clear_and_config_cache(): void
    {
        $this->onHosting('DEV');
        Process::fake();

        $this->post(route('admin.server-tools.run'), ['action' => 'config_clear'])->assertSessionHas('success');
        $this->post(route('admin.server-tools.run'), ['action' => 'config_cache'])->assertSessionHas('success');

        Process::assertRan(fn(PendingProcess $process) => $process->command === ['/opt/cpanel/ea-php82/root/usr/bin/php', base_path('artisan'), 'config:clear']);
        Process::assertRan(fn(PendingProcess $process) => $process->command === ['/opt/cpanel/ea-php82/root/usr/bin/php', base_path('artisan'), 'config:cache']);
    }

    public function test_laravel_log_is_shown_newest_first_and_can_be_downloaded_and_cleared(): void
    {
        $logFile = $this->tmp . '/laravel.log';
        File::ensureDirectoryExists($this->tmp);
        File::put($logFile, "[2026-11-10 08:00:00] local.INFO: first entry\n[2026-11-10 09:00:00] local.ERROR: something broke\n");
        config(['server_tools.log_file' => $logFile]);

        $html = $this->get(route('admin.server-tools.index'))->assertOk()->getContent();
        $this->assertStringContainsString('<span class="log-error">[2026-11-10 09:00:00] local.ERROR: something broke</span>', $html);
        $this->assertLessThan(strpos($html, 'first entry'), strpos($html, 'something broke'));

        $this->get(route('admin.server-tools.log.download'))->assertOk()->assertDownload();

        $this->delete(route('admin.server-tools.log.clear'))->assertSessionHas('success');
        $this->assertSame('', File::get($logFile));
        $this->get(route('admin.server-tools.index'))->assertSee('The log is empty.');
    }

    public function test_a_big_log_is_read_from_the_end_only(): void
    {
        $logFile = $this->tmp . '/laravel.log';
        File::ensureDirectoryExists($this->tmp);
        File::put($logFile, str_repeat("old line padding padding padding\n", 40000) . "the very last line\n");
        config(['server_tools.log_file' => $logFile, 'server_tools.log_lines' => 5]);

        $tail = \App\Support\ServerTools::logTail();

        $this->assertCount(5, $tail['lines']);
        $this->assertSame('the very last line', $tail['lines'][0]);
        $this->assertGreaterThan(512 * 1024, $tail['size']);
    }

    public function test_page_is_in_the_system_menu_and_off_on_the_local_machine(): void
    {
        config(['server_tools.enabled' => false]);

        $this->get(route('admin.server-tools.index'))
            ->assertOk()
            ->assertSee('<span>Server Tools</span>', false)
            ->assertSee('Server Tools are off here.')
            ->assertSee('Clear caches')
            ->assertSee('Composer install')
            ->assertSee('Copy public folders')
            ->assertSee('disabled', false);
    }

    public function test_nothing_runs_on_the_local_machine(): void
    {
        config(['server_tools.enabled' => false]);
        Process::fake();

        $this->post(route('admin.server-tools.run'), ['action' => 'migrate'])
            ->assertRedirect(route('admin.server-tools.index'))
            ->assertSessionHas('error');

        Process::assertNothingRan();
    }

    public function test_unknown_action_is_refused(): void
    {
        $this->onHosting();
        Process::fake();

        $this->post(route('admin.server-tools.run'), ['action' => 'rm -rf'])->assertSessionHasErrors('action');

        Process::assertNothingRan();
    }

    public function test_commands_run_with_the_cpanel_php_binary_and_are_logged(): void
    {
        $this->onHosting();
        Process::fake(['*' => Process::result(output: 'Caches cleared successfully.')]);

        $this->post(route('admin.server-tools.run'), ['action' => 'optimize_clear'])
            ->assertRedirect(route('admin.server-tools.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('tool_output', fn($output) => str_contains($output, 'Caches cleared successfully.'));

        Process::assertRan(fn(PendingProcess $process) => $process->command === [
            '/opt/cpanel/ea-php82/root/usr/bin/php', base_path('artisan'), 'optimize:clear',
        ]);

        $this->post(route('admin.server-tools.run'), ['action' => 'migrate'])->assertSessionHas('success');
        Process::assertRan(fn(PendingProcess $process) => array_slice($process->command, 2) === ['migrate', '--force', '--no-interaction']);

        $this->assertSame(2, ActivityLog::query()->where('action', 'server_tool_run')->count());
    }

    public function test_a_failed_command_shows_its_output(): void
    {
        $this->onHosting();
        Process::fake(['*' => Process::result(errorOutput: 'SQLSTATE[HY000] Access denied', exitCode: 1)]);

        $this->post(route('admin.server-tools.run'), ['action' => 'migrate'])
            ->assertSessionHas('error')
            ->assertSessionHas('tool_output', fn($output) => str_contains($output, 'Access denied') && str_contains($output, 'Exit code: 1'));
    }

    public function test_composer_needs_composer_phar(): void
    {
        $this->onHosting();
        config(['server_tools.composer' => $this->tmp . '/missing/composer.phar']);
        Process::fake();

        $this->post(route('admin.server-tools.run'), ['action' => 'composer'])
            ->assertSessionHas('error')
            ->assertSessionHas('tool_output', fn($output) => str_contains($output, 'composer.phar was not found'));

        Process::assertNothingRan();
    }

    public function test_public_folders_are_copied_to_public_html(): void
    {
        $this->onHosting();
        File::ensureDirectoryExists($this->tmp . '/project/public/css');
        File::ensureDirectoryExists($this->tmp . '/project/public/sounds');
        File::ensureDirectoryExists($this->tmp . '/public_html/css');
        File::put($this->tmp . '/project/public/css/site.css', 'new');
        File::put($this->tmp . '/project/public/sounds/ding.wav', 'wav');
        File::put($this->tmp . '/public_html/css/site.css', 'old');
        File::put($this->tmp . '/public_html/css/keep.css', 'only in public_html');

        config([
            'server_tools.public_source' => $this->tmp . '/project/public',
            'server_tools.public_target' => $this->tmp . '/public_html',
        ]);

        $this->post(route('admin.server-tools.run'), ['action' => 'copy_public'])
            ->assertSessionHas('success')
            ->assertSessionHas('tool_output', fn($output) => str_contains($output, '✓ css') && str_contains($output, 'main: not in the project'));

        $this->assertSame('new', File::get($this->tmp . '/public_html/css/site.css'));
        $this->assertSame('wav', File::get($this->tmp . '/public_html/sounds/ding.wav'));
        // Files only in public_html are kept.
        $this->assertSame('only in public_html', File::get($this->tmp . '/public_html/css/keep.css'));
    }

    public function test_copy_writes_only_into_a_public_html_folder(): void
    {
        $this->onHosting();
        File::ensureDirectoryExists($this->tmp . '/project/public/css');
        File::ensureDirectoryExists($this->tmp . '/somewhere_else');

        config([
            'server_tools.public_source' => $this->tmp . '/project/public',
            'server_tools.public_target' => $this->tmp . '/somewhere_else',
        ]);

        $this->post(route('admin.server-tools.run'), ['action' => 'copy_public'])
            ->assertSessionHas('error')
            ->assertSessionHas('tool_output', fn($output) => str_contains($output, 'must be an existing public_html folder'));

        $this->assertFalse(File::isDirectory($this->tmp . '/somewhere_else/css'));
    }
}
