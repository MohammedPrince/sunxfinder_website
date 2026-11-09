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

    /** Pretend this is the hosting. */
    private function onHosting(): void
    {
        config(['server_tools.enabled' => true, 'server_tools.php' => '/opt/cpanel/ea-php82/root/usr/bin/php']);
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
