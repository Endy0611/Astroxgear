<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        /**
         * ------------------------------------------------------------
         * GLOBAL MIDDLEWARE
         * ------------------------------------------------------------
         */
        // ✅ ENABLE CORS (THIS IS THE KEY FIX)
        $middleware->append(HandleCors::class);

        /**
         * ------------------------------------------------------------
         * API MIDDLEWARE
         * ------------------------------------------------------------
         */
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /**
         * ------------------------------------------------------------
         * WEB MIDDLEWARE
         * ------------------------------------------------------------
         */
        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);

        /**
         * ------------------------------------------------------------
         * MIDDLEWARE ALIASES
         * ------------------------------------------------------------
         */
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'firebase' => \App\Http\Middleware\FirebaseAuth::class, 
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);

        /**
         * ------------------------------------------------------------
         * CSRF (API DOES NOT NEED CSRF)
         * ------------------------------------------------------------
         */
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();