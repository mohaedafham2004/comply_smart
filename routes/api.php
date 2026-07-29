<?php

use App\Http\Controllers\Api\AIChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RenewalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ComplySmart v1
|--------------------------------------------------------------------------
|
| Prefix /api is automatic (set in bootstrap/app.php withRouting).
| Rate limiters are defined in App\Providers\AppServiceProvider::boot().
|
*/

// ─── Health Check ──────────────────────────────────────────────────────────
Route::get('/v1/ping', fn () => response()->json([
    'status'  => 'ok',
    'version' => '1.0',
    'app'     => config('app.name'),
    'time'    => now()->toIso8601String(),
]));

// ─── Dev-only: Trigger reminders manually ──────────────────────────────────
// Runs the daily scheduler command synchronously — for testing without waiting.
// Protected by Sanctum so only logged-in users can trigger it.
Route::middleware('auth:sanctum')->post('/v1/test-reminders', function () {
    if (!app()->environment('production')) {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('complysmart:send-reminders');
        $output   = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'message'   => 'Reminder command executed.',
            'exit_code' => $exitCode,
            'output'    => $output,
        ]);
    }
    return response()->json(['message' => 'Not available in production.'], 403);
});


// ─── Auth — Public ─────────────────────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {

    // Registration: no rate limit (bcrypt is already slow)
    Route::post('/register', [AuthController::class, 'register']);

    // Login: 5 attempts per minute per IP
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// ─── Protected Routes ──────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // ── My Business (current user's own business — no ID in URL) ───────────
    Route::get('/business',  [BusinessController::class, 'show']);
    Route::put('/business',  [BusinessController::class, 'update']);

    // ── Businesses (admin / general listing) ───────────────────────────────
    Route::get('/businesses',         [BusinessController::class, 'index']);
    Route::post('/businesses',        [BusinessController::class, 'store']);
    Route::get('/businesses/{id}',    [BusinessController::class, 'showById']);
    Route::put('/businesses/{id}',    [BusinessController::class, 'updateById']);

    // ── Documents — flat routes (primary) ─────────────────────────────────
    Route::get('/documents',             [DocumentController::class, 'index']);
    Route::post('/documents',            [DocumentController::class, 'store']);
    Route::get('/documents/{id}',        [DocumentController::class, 'show']);
    Route::put('/documents/{id}',        [DocumentController::class, 'update']);
    Route::delete('/documents/{id}',     [DocumentController::class, 'destroy']);
    Route::get('/documents/{id}/ocr-status', [DocumentController::class, 'ocrStatus']);

    // ── Documents — legacy nested (backward compat) ────────────────────────
    Route::get('/businesses/{businessId}/documents',  [DocumentController::class, 'indexByBusiness']);
    Route::post('/businesses/{businessId}/documents', [DocumentController::class, 'storeForBusiness']);


    // ── Compliance Score ───────────────────────────────────────────────────
    Route::get('/business/compliance-score', [BusinessController::class, 'complianceScore']);

    // ── Tasks — flat routes ────────────────────────────────────────────────
    Route::get('/tasks',                   [TaskController::class, 'index']);
    Route::post('/tasks',                  [TaskController::class, 'store']);
    Route::get('/tasks/{id}',              [TaskController::class, 'show']);
    Route::put('/tasks/{id}',              [TaskController::class, 'update']);
    Route::delete('/tasks/{id}',           [TaskController::class, 'destroy']);
    Route::patch('/tasks/{id}/status',     [TaskController::class, 'updateStatus']);

    // ── Renewals — flat routes ─────────────────────────────────────────────
    Route::get('/renewals/upcoming',       [RenewalController::class, 'upcoming']);  // BEFORE /{id}
    Route::get('/renewals',                [RenewalController::class, 'index']);
    Route::post('/renewals',               [RenewalController::class, 'store']);
    Route::get('/renewals/{id}',           [RenewalController::class, 'show']);
    Route::put('/renewals/{id}',           [RenewalController::class, 'update']);
    Route::delete('/renewals/{id}',        [RenewalController::class, 'destroy']);

    // ── Notifications ──────────────────────────────────────────────────────
    Route::get('/notifications',                    [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all',         [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read',        [NotificationController::class, 'markRead']);

    // ── AI — rate-limited to 20 req/min per user ───────────────────────────
    Route::middleware('throttle:ai')->group(function () {
        Route::post('/ai/chat',           [AIChatController::class, 'chat']);
        Route::get('/ai/chat/history',    [AIChatController::class, 'history']);
        Route::post('/ai/checklist',      [AIChatController::class, 'generateChecklist']);
        Route::post('/ai/summarize',      [AIChatController::class, 'summarize']);
    });


    // ── Reports & Analytics ────────────────────────────────────────────────
    Route::get('/reports/overview',                   [ReportController::class, 'overview']);
    Route::get('/reports/audit-log',                  [ReportController::class, 'auditLog'])->middleware('role:admin');
    Route::get('/businesses/{id}/report/compliance',  [ReportController::class, 'complianceSummary']);
    Route::get('/businesses/{id}/report/audit',       [ReportController::class, 'auditTrail']);
});


