<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\BusinessRepositoryInterface;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\AuditLogRepository;
use App\Repositories\BusinessRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\RenewalRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ─── Bind Repository Interfaces → Concrete Implementations ───────────
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(BusinessRepositoryInterface::class, BusinessRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(RenewalRepositoryInterface::class, RenewalRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override Sanctum's default PersonalAccessToken with our MongoDB-backed model
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // ── Rate Limiters ──────────────────────────────────────────────────────

        // AI endpoints: 20 requests / minute per authenticated user (by user ID).
        // Falls back to IP for unauthenticated (shouldn't happen since routes need auth).
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->getKey() ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many AI requests. Please wait a moment and try again.',
                ], 429));
        });

        // Global API rate limiter: 60 requests per minute per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->getKey() ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many requests. Please slow down.',
                ], 429));
        });

        // Login: 5 attempts / minute per IP (mirrors the route-level throttle)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

    }
}
