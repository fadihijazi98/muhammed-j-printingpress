<?php

declare(strict_types=1);

/**
 * Entry point for PHP's built-in server, started with `-t public`.
 * Real files inside public/ are served by the server itself; every other URL
 * goes to the front controller.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/public' . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/public/index.php';
