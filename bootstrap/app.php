<?php

use App\Exceptions\InvalidReservationTransition;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\EnsureSupervisor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\TrackAffiliateReferral::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'api.auth' => ApiAuthenticate::class,
            'supervisor' => EnsureSupervisor::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'booking/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A refused booking status change is a user error, not a crash: explain it.
        $exceptions->render(function (InvalidReservationTransition $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        });
        $exceptions->dontReport(InvalidReservationTransition::class);
    })->create();
