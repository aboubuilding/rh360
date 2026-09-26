<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: [
        __DIR__.'/../routes/web.php',
        __DIR__.'/../routes/web/administration.php',
        __DIR__.'/../routes/web/organisation.php',
        __DIR__.'/../routes/web/classification.php',
        __DIR__.'/../routes/web/personnel.php',

         __DIR__.'/../routes/web/contrats.php',
    ],
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\VerifierPermission::class,
    ]);

    $middleware->redirectGuestsTo(fn () => route('login'));
    $middleware->redirectUsersTo(fn () => route('dashboard'));
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
