<?php

/*
 * Admin → System → Server Tools: deploy commands that only run on the cPanel hosting.
 * Locally (Windows / WAMP) the binaries below do not exist, so every button is disabled.
 * All values can be changed in .env without touching code.
 */
return [
    // Force on/off. Empty = on only when the PHP binary below exists and the OS is not Windows.
    'enabled' => env('SERVER_TOOLS_ENABLED'),

    // cPanel's PHP 8.2 command-line binary (the web PHP cannot run artisan/composer itself).
    'php' => env('SERVER_TOOLS_PHP', '/opt/cpanel/ea-php82/root/usr/bin/php'),

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

    // Seconds a command may run (composer install is the slowest).
    'timeout' => (int) env('SERVER_TOOLS_TIMEOUT', 600),

    // Log viewer: storage/logs/laravel.log, newest lines first. Only the end of the file is read.
    'log_file' => storage_path('logs/laravel.log'),
    'log_lines' => 300,
];
