<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * POST /api/v1/auth/register
     *
     * Public — no rate limit (registration is already slow due to bcrypt).
     * Creates the user AND their business shell in one atomic operation.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful.',
            'data'    => $result,
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     *
     * Rate-limited: 5 attempts / minute per IP (applied in routes/api.php via throttle middleware).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful.',
            'data'    => $result,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/v1/auth/me
     *
     * Returns the authenticated user with their business relationship loaded.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return response()->json(['data' => $user]);
    }
}
