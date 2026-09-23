<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/auth.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
        ]);

        // Inertia: resolve the root Blade view + shared props on every web request.
        // Appended to the web group so it wraps the existing session/auth stack.
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Send guests to the login page (Laravel default is 'login' route, which
        // now exists in routes/auth.php — kept explicit for clarity).
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         | Friendly Inertia error pages (brief §41). Only Inertia/browser GET
         | navigations get the React error page; JSON/XHR callers still receive the
         | normal Laravel error response, and in debug mode we keep the detailed
         | Laravel error page so real exceptions stay visible during development.
         */
        $exceptions->respond(function ($response, $e, SymfonyRequest $request) {
            if (! app()->environment('production')) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), [403, 404, 500, 503], true)) {
                return $response;
            }

            if (! $request->header('X-Inertia') && ! $request->expectsJson()) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $response->getStatusCode()])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
