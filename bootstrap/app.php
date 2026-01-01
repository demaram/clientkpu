<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'client.auth' => \App\Http\Middleware\ClientAuth::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'role' => \App\Http\Middleware\LaravelEntrustRole::class,
            'permission' => \App\Http\Middleware\LaravelEntrustPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
