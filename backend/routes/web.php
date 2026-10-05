<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ComplySmart API Service
|--------------------------------------------------------------------------
|
| The ComplySmart frontend is decoupled and runs on Vite (port 5173).
| Web requests directly to backend port 8000 return API status information.
|
*/

Route::get('/', fn () => response()->json([
    'service' => 'ComplySmart Backend API',
    'status' => 'online',
    'version' => '1.0',
    'frontend' => 'http://localhost:5173',
    'documentation' => [
        'ping' => '/api/v1/ping',
        'auth_login' => '/api/v1/auth/login',
        'auth_register' => '/api/v1/auth/register',
        'business' => '/api/v1/business',
        'documents' => '/api/v1/documents',
        'tasks' => '/api/v1/tasks',
        'renewals' => '/api/v1/renewals',
        'notifications' => '/api/v1/notifications',
        'ai' => '/api/v1/ai/chat',
        'reports' => '/api/v1/reports/overview'
    ]
]));

Route::fallback(fn () => response()->json([
    'error' => 'Not Found',
    'message' => 'This is the ComplySmart Backend API. Frontend is served at http://localhost:5173'
], 404));
