<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', App\Http\Middleware\EncryptCookies::class);
        $middleware->appendToGroup('web', Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
        $middleware->appendToGroup('web', Illuminate\Session\Middleware\StartSession::class);
        $middleware->appendToGroup('web', Illuminate\View\Middleware\ShareErrorsFromSession::class);
        $middleware->appendToGroup('web', App\Http\Middleware\VerifyCsrfToken::class);
        $middleware->appendToGroup('web', Illuminate\Routing\Middleware\SubstituteBindings::class);
        $middleware->appendToGroup('web', App\Http\Middleware\TrimStrings::class);
        $middleware->appendToGroup('web', Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);

        $middleware->appendToGroup('api', Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1');
        $middleware->appendToGroup('api', Illuminate\Routing\Middleware\SubstituteBindings::class);

        $middleware->alias([
            'auth' => App\Http\Middleware\Authenticate::class,
            'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
            'signed' => App\Http\Middleware\ValidateSignature::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
