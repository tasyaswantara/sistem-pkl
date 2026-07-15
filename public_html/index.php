<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = getenv('APP_BASE_PATH') ?: (realpath(__DIR__ . '/..') ?: dirname(__DIR__));

if (! str_starts_with($basePath, DIRECTORY_SEPARATOR)) {
    $basePath = dirname(__DIR__).DIRECTORY_SEPARATOR.ltrim($basePath, DIRECTORY_SEPARATOR);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once $basePath.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
