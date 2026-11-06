<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    // Οι διευθύνσεις των σελίδων μας ορίζονται στο routes/web.php.
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Το auth middleware καλεί αυτόν τον κανόνα όταν ένας guest ανοίξει προστατευμένη σελίδα.
        $middleware->redirectGuestsTo(
            fn (Request $request): string => match (true) {
                $request->is('student/*') => route('student.login'),
                $request->is('provider/*') => route('provider.login'),
                $request->is('scientific-supervisor/*') => route('scientific_supervisor.login'),
                default => route('login'), // Login χειριστή / admin.
            },
        );

        // Το guest middleware καλεί αυτόν τον κανόνα όταν συνδεδεμένος χρήστης ανοίξει σελίδα login.
        $middleware->redirectUsersTo(function (): string {
            if (auth('web')->check()) {
                return route('dashboard');
            }

            if (auth('student')->check()) {
                return route('student.dashboard');
            }

            if (auth('provider')->check()) {
                return route('provider.dashboard');
            }

            if (auth('scientific_supervisor')->check()) {
                return route('scientific_supervisor.dashboard');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
