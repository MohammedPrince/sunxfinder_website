<?php

/*
 * Admin → System → Server Tools: deploy commands that only run on the cPanel hosting.
 * Locally (Windows / WAMP) the binaries below do not exist, so every button is disabled.
 * All values can be changed in .env without touching code.
 */
return [
    // Force on/off. Empty = on only when the PHP binary below exists and the OS is not Windows.
    'enabled' => env('SERVER_TOOLS_ENABLED'),

    // Command-line PHP for artisan/composer (the web PHP cannot run them itself).
    // Empty = the CLI binary of the same version the website runs, found among php_candidates,
    // so changing the version in cPanel's PHP selector needs no change here.
    'php' => env('SERVER_TOOLS_PHP'),

    // {v} = the website's PHP version without the dot (8.4 → 84). First existing file wins.
    // /opt/alt = CloudLinux PHP selector (what SSH `php` uses), /opt/cpanel = EasyApache.
    'php_candidates' => [
        '/opt/alt/php{v}/usr/bin/php',
        '/opt/cpanel/ea-php{v}/root/usr/bin/php',
    ],

    'composer' => env('SERVER_TOOLS_COMPOSER', '/opt/cpanel/ea-wappspector/composer.phar'),

    // Which site this copy is, from the project folder name under public_html/Repository.
    // copy_public: only PROD copies its public folders to public_html (dev serves its own).
    // An unknown folder name gets no copy, to stay safe.
    'sites' => [
        'sunxfinder_prod' => ['name' => 'PROD', 'url' => 'https://sunxfinder.com', 'copy_public' => true],
        'sunXfinder' => ['name' => 'DEV', 'url' => 'https://dev.sunxfinder.com', 'copy_public' => false],
    ],

    // Copy public assets: <project>/public/{folders} → public_html/{folders}.
    // On the hosting the project is public_html/Repository/sunxfinder_prod, so public_html is two levels up.
    'public_source' => env('SERVER_TOOLS_PUBLIC_SOURCE', base_path('public')),
    'public_target' => env('SERVER_TOOLS_PUBLIC_TARGET', dirname(base_path(), 2)),
    'public_folders' => ['main', 'admin', 'company', 'visitors', 'sounds', 'css'],
    // Single files copied with them (patterns). Never index.php or .htaccess: public_html has its own.
    'public_files' => ['favicon.ico', 'favicon.svg', 'google*.html'],

    // Seconds a command may run (composer install is the slowest).
    'timeout' => (int) env('SERVER_TOOLS_TIMEOUT', 600),

    // Log viewer: storage/logs/laravel.log, newest lines first. Only the end of the file is read.
    'log_file' => storage_path('logs/laravel.log'),
    'log_lines' => 300,
];
