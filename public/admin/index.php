<?php

/*
 * "/admin" is both a Laravel route and this assets folder (css/js/images).
 * Servers that answer folders themselves (php artisan serve, or Apache/LiteSpeed
 * without the .htaccess rule) run this file and would otherwise show "Not Found".
 * Hand the request to Laravel; public/index.php fixes the base path, so /admin
 * redirects to the dashboard or the login page.
 */

chdir(dirname(__DIR__));

require dirname(__DIR__) . '/index.php';
