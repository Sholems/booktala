<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'api.auth' => \App\Http\Middleware\ApiAuthenticate::class,
            'supervisor' => \App\Http\Middleware\EnsureSupervisor::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'booking/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A refused booking status change is a user error, not a crash: explain it.
        $exceptions->render(function (\App\Exceptions\InvalidReservationTransition $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        });
        $exceptions->dontReport(\App\Exceptions\InvalidReservationTransition::class);
    })->create();
