<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// public/admin and public/company are asset folders that also hold a small index.php,
// so /admin and /company reach Laravel on any server. Some servers (php artisan serve)
// then report SCRIPT_NAME as /admin/index.php for every /admin/* URL, which would make
// Laravel treat /admin as the site's base path. Always run as the root front controller.
$_SERVER['SCRIPT_NAME'] = preg_replace('#/(admin|company)/index\.php$#', '/index.php', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
