<?php

use App\Http\Middleware\AuditLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // ── Daily Reminders ────────────────────────────────────────────────────
        // Runs every day at 08:00 server time.
        // To test manually: php artisan complysmart:send-reminders
        // To test dry-run:  php artisan complysmart:send-reminders --dry-run
        $schedule->command('complysmart:send-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ── Mark overdue tasks (also done inside send-reminders, but belt+braces) ─
        $schedule->command('complysmart:send-reminders --days=0')
            ->dailyAt('00:05')
            ->withoutOverlapping();
    })

    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS handling
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Sanctum stateful middleware for the api group (enables SPA auth + cookie sessions)
        $middleware->statefulApi();

        // Append global rate limiting and AuditLogger to the api group
        $middleware->appendToGroup('api', 'throttle:api');
        $middleware->appendToGroup('api', AuditLogger::class);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for API errors
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });
    })->create();

