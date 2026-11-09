<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

/**
 * Admin → System → Server Tools. Runs the deploy commands on the cPanel hosting:
 *
 *   php artisan optimize:clear | optimize | migrate --force
 *   php artisan queue:work --stop-when-empty   (run waiting jobs now)
 *   php composer.phar install --no-dev --optimize-autoloader --ignore-platform-reqs
 *   copy public/{main,admin,company,visitors,sounds,css} to public_html
 *
 * Nothing runs on the local WAMP machine: the cPanel PHP binary does not exist there.
 * Each run is locked (one at a time), time-limited and written to the activity log.
 */
class ServerTools
{
    /** Action key => what the admin sees. Order = order on the page. */
    public const ACTIONS = [
        'optimize_clear' => [
            'title' => 'Clear caches',
            'command' => 'php artisan optimize:clear',
            'help' => 'Clears the config, route, view, event and app caches. Run it first after uploading new code or changing .env.',
            'confirm' => 'Clear all caches now?',
        ],
        'optimize' => [
            'title' => 'Optimize',
            'command' => 'php artisan optimize',
            'help' => 'Builds the config, route and view caches again so the site is fast. Run it after "Clear caches".',
            'confirm' => 'Rebuild the caches now?',
        ],
        'migrate' => [
            'title' => 'Run migrations',
            'command' => 'php artisan migrate --force',
            'help' => 'Applies new database changes from the uploaded code. Take a database backup first.',
            'confirm' => 'Run the database migrations on the LIVE database? Make sure you have a fresh backup.',
            'danger' => true,
        ],
        'queue' => [
            'title' => 'Run waiting jobs',
            'command' => 'php artisan queue:work --stop-when-empty',
            'help' => 'Sends the emails and pushes waiting in the queue now, then stops. Use it when the queue cron is late or was down.',
            'confirm' => 'Run the waiting jobs now?',
        ],
        'composer' => [
            'title' => 'Composer install',
            'command' => 'php composer.phar install --no-dev --optimize-autoloader --ignore-platform-reqs',
            'help' => 'Installs the PHP packages from composer.lock (the vendor folder). Run it when composer.json or composer.lock changed. Can take a few minutes.',
            'confirm' => 'Run composer install now? It can take a few minutes.',
            'danger' => true,
        ],
        'copy_public' => [
            'title' => 'Copy public folders',
            'command' => 'copy public/{main,admin,company,visitors,sounds,css} → public_html',
            'help' => 'Copies the CSS, JS, images and sounds from the project\'s public folder to public_html, where the website reads them. Run it after uploading new design or sound files.',
            'confirm' => 'Copy the public folders to public_html? Files with the same name are replaced.',
        ],
    ];

    private const LOCK = 'server-tools:running';

    /** True on the hosting: not Windows and the cPanel PHP binary exists (or SERVER_TOOLS_ENABLED forces it). */
    public static function available(): bool
    {
        $forced = config('server_tools.enabled');

        if ($forced !== null && $forced !== '') {
            return filter_var($forced, FILTER_VALIDATE_BOOLEAN);
        }

        return PHP_OS_FAMILY !== 'Windows' && @is_file((string) config('server_tools.php'));
    }

    /** Why the tools are off, in one sentence (null when they are available). */
    public static function unavailableReason(): ?string
    {
        if (self::available()) {
            return null;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return 'This is the local (Windows/WAMP) copy. Server Tools work only on the hosting.';
        }

        return 'The cPanel PHP binary was not found at ' . config('server_tools.php') . '. Set SERVER_TOOLS_PHP in .env.';
    }

    /** Can the web PHP start other programs? Shared hosting sometimes turns proc_open off. */
    public static function canRunProcesses(): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists('proc_open') && !in_array('proc_open', $disabled, true);
    }

    /** Paths and checks shown on the page. */
    public static function status(): array
    {
        $source = rtrim((string) config('server_tools.public_source'), '/\\');
        $target = rtrim((string) config('server_tools.public_target'), '/\\');

        return [
            'available' => self::available(),
            'reason' => self::unavailableReason(),
            'processes' => self::canRunProcesses(),
            'php' => (string) config('server_tools.php'),
            'composer' => (string) config('server_tools.composer'),
            'composer_found' => @is_file((string) config('server_tools.composer')),
            'base_path' => base_path(),
            'public_source' => $source,
            'public_target' => $target,
            'public_folders' => config('server_tools.public_folders'),
            'running' => Cache::has(self::LOCK),
        ];
    }

    /**
     * Runs one action. Returns ['success' => bool, 'output' => string, 'seconds' => float].
     *
     * @throws RuntimeException when the action cannot run here
     */
    public static function run(string $action): array
    {
        if (!array_key_exists($action, self::ACTIONS)) {
            throw new RuntimeException('Unknown action.');
        }

        if (!self::available()) {
            throw new RuntimeException(self::unavailableReason());
        }

        // One run at a time: two composer installs or migrations together would break things.
        $lock = Cache::lock(self::LOCK, (int) config('server_tools.timeout') + 60);

        if (!$lock->get()) {
            throw new RuntimeException('Another server tool is still running. Wait for it to finish, then try again.');
        }

        @set_time_limit((int) config('server_tools.timeout') + 30);
        $started = microtime(true);

        try {
            [$success, $output] = $action === 'copy_public'
                ? self::copyPublicFolders()
                : self::runCommand($action);
        } catch (Throwable $e) {
            report($e);
            [$success, $output] = [false, $e->getMessage()];
        } finally {
            $lock->release();
        }

        return [
            'success' => $success,
            'output' => trim($output) !== '' ? trim($output) : '(no output)',
            'seconds' => round(microtime(true) - $started, 1),
        ];
    }

    /** @return array{0: bool, 1: string} */
    private static function runCommand(string $action): array
    {
        if (!self::canRunProcesses()) {
            throw new RuntimeException('This hosting has proc_open turned off, so commands cannot run from the panel. Run them over SSH or ask the host to enable proc_open.');
        }

        $php = (string) config('server_tools.php');
        $artisan = base_path('artisan');

        $command = match ($action) {
            'optimize_clear' => [$php, $artisan, 'optimize:clear'],
            'optimize' => [$php, $artisan, 'optimize'],
            'migrate' => [$php, $artisan, 'migrate', '--force', '--no-interaction'],
            // Stops when the queue is empty, and never runs longer than the page can wait.
            'queue' => [$php, $artisan, 'queue:work', '--stop-when-empty', '--tries=3', '--max-time=' . max(60, (int) config('server_tools.timeout') - 60)],
            'composer' => [$php, (string) config('server_tools.composer'), 'install', '--no-dev', '--optimize-autoloader', '--ignore-platform-reqs', '--no-interaction', '--no-progress'],
        };

        if ($action === 'composer' && !@is_file((string) config('server_tools.composer'))) {
            throw new RuntimeException('composer.phar was not found at ' . config('server_tools.composer') . '. Set SERVER_TOOLS_COMPOSER in .env.');
        }

        // Composer needs a writable home folder; the web user usually has none.
        $composerHome = storage_path('app/composer');
        File::ensureDirectoryExists($composerHome);

        $result = Process::path(base_path())
            ->timeout((int) config('server_tools.timeout'))
            ->env(['COMPOSER_HOME' => $composerHome, 'HOME' => $composerHome])
            ->run($command);

        $output = trim($result->output() . "\n" . $result->errorOutput());

        return [$result->successful(), "$ " . implode(' ', $command) . "\n\n" . $output . "\n\nExit code: " . $result->exitCode()];
    }

    /** @return array{0: bool, 1: string} */
    private static function copyPublicFolders(): array
    {
        $source = rtrim((string) config('server_tools.public_source'), '/\\');
        $target = rtrim((string) config('server_tools.public_target'), '/\\');

        // Safety: only ever write into the hosting's public_html, never inside the project.
        if (basename($target) !== 'public_html' || !File::isDirectory($target)) {
            throw new RuntimeException("The target folder must be an existing public_html folder. Got: {$target}. Set SERVER_TOOLS_PUBLIC_TARGET in .env.");
        }

        if (!File::isDirectory($source) || str_starts_with($target . '/', $source . '/')) {
            throw new RuntimeException("The source folder {$source} was not found.");
        }

        $lines = ["Copy {$source} → {$target}", ''];
        $success = true;

        foreach (config('server_tools.public_folders') as $folder) {
            $from = $source . '/' . $folder;
            $to = $target . '/' . $folder;

            if (!File::isDirectory($from)) {
                $lines[] = "– {$folder}: not in the project, skipped";
                continue;
            }

            // Replaces files with the same name; files only in public_html are kept.
            if (File::copyDirectory($from, $to)) {
                $lines[] = "✓ {$folder}: " . count(File::allFiles($from)) . ' files copied';
            } else {
                $lines[] = "✗ {$folder}: copy failed (check folder permissions)";
                $success = false;
            }
        }

        return [$success, implode("\n", $lines)];
    }
}
