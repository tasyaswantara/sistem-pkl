<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$basePath = env('APP_BASE_PATH', dirname(__DIR__));

if (! str_starts_with($basePath, DIRECTORY_SEPARATOR)) {
    $basePath = dirname(__DIR__).DIRECTORY_SEPARATOR.ltrim($basePath, DIRECTORY_SEPARATOR);
}

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'industri.approved' => \App\Http\Middleware\EnsureIndustriApproved::class,
        ]);
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$publicPath = env('APP_PUBLIC_PATH', 'public');

if ($publicPath !== '') {
    $publicPath = str_starts_with($publicPath, DIRECTORY_SEPARATOR)
        ? $publicPath
        : dirname(__DIR__).DIRECTORY_SEPARATOR.ltrim($publicPath, DIRECTORY_SEPARATOR);

    if (is_dir($publicPath)) {
        $app->usePublicPath($publicPath);
    }
}

return $app;
