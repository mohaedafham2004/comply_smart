<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Renewal\CreateRenewalRequest;
use App\Http\Requests\Renewal\UpdateRenewalRequest;
use App\Services\RenewalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RenewalController extends Controller
{
    public function __construct(private readonly RenewalService $renewalService) {}

    // ─── GET /api/v1/renewals ─────────────────────────────────────────────────
    /**
     * List renewals for the current user's business.
     * Query param: status (upcoming|due|overdue|completed)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $renewals = $this->renewalService->listForUser(
                $request->user(),
                $request->only('status')
            );
            return response()->json(['data' => $renewals]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    // ─── GET /api/v1/renewals/upcoming ───────────────────────────────────────
    /**
     * Renewals due in the next N days (default 30).
     * Query param: days (int, default 30)
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $days     = $request->integer('days', 30);
            $renewals = $this->renewalService->upcomingForUser($request->user(), $days);
            return response()->json([
                'data' => $renewals,
                'meta' => ['days_ahead' => $days, 'count' => $renewals->count()],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    // ─── POST /api/v1/renewals ────────────────────────────────────────────────
    public function store(CreateRenewalRequest $request): JsonResponse
    {
        try {
            $renewal = $this->renewalService->create($request->user(), $request->validated());
            return response()->json(['message' => 'Renewal created.', 'data' => $renewal], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ─── GET /api/v1/renewals/{id} ────────────────────────────────────────────
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $renewal = $this->renewalService->findForUser($request->user(), $id);
            return response()->json(['data' => $renewal]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── PUT /api/v1/renewals/{id} ────────────────────────────────────────────
    public function update(UpdateRenewalRequest $request, string $id): JsonResponse
    {
        try {
            $renewal = $this->renewalService->update($request->user(), $id, $request->validated());
            return response()->json(['message' => 'Renewal updated.', 'data' => $renewal]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── DELETE /api/v1/renewals/{id} ─────────────────────────────────────────
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->renewalService->delete($request->user(), $id);
            return response()->json(['message' => 'Renewal deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
