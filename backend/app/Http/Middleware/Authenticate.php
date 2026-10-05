<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * This is an API-only app — never redirect, always return null so Laravel
     * throws AuthenticationException which our exception handler in bootstrap/app.php
     * converts to a clean 401 JSON response.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
