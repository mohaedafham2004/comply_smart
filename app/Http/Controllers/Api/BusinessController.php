<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Services\BusinessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function __construct(private readonly BusinessService $businessService) {}

    // ─── "My Business" endpoints ────────────────────────────────────────────
    // These operate on the current user's linked business (no {id} in URL).

    /**
     * GET /api/v1/business
     *
     * Returns the current user's business profile.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $business = $this->businessService->getForUser($request->user());
            return response()->json(['data' => $business]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * PUT /api/v1/business
     *
     * Update the current user's business profile.
     * Restricted to owner or admin roles.
     */
    public function update(UpdateBusinessRequest $request): JsonResponse
    {
        try {
            $business = $this->businessService->updateForUser(
                $request->user(),
                $request->validated(),
            );

            return response()->json([
                'message' => 'Business profile updated.',
                'data'    => $business,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * GET /api/v1/business/compliance-score
     *
     * Returns the live compliance score breakdown:
     * overall score, task stats, renewal stats, and the component sub-scores.
     */
    public function complianceScore(Request $request): JsonResponse
    {
        try {
            $data = $this->businessService->getComplianceScore($request->user());
            return response()->json(['data' => $data]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }


    // ─── Admin / multi-business endpoints ───────────────────────────────────

    /** GET /api/v1/businesses */
    public function index(Request $request): JsonResponse
    {
        $businesses = $this->businessService->listForUser($request->user());
        return response()->json(['data' => $businesses]);
    }

    /** POST /api/v1/businesses */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|string|max:100',
            'registration_no' => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
        ]);

        $business = $this->businessService->create($request->user(), $request->validated());
        return response()->json(['message' => 'Business created.', 'data' => $business], 201);
    }

    /** GET /api/v1/businesses/{id} */
    public function showById(string $id): JsonResponse
    {
        $business = $this->businessService->findById($id);
        if (!$business) return response()->json(['message' => 'Not found.'], 404);
        return response()->json(['data' => $business]);
    }

    /** PUT /api/v1/businesses/{id} (admin use) */
    public function updateById(Request $request, string $id): JsonResponse
    {
        $business = $this->businessService->findById($id);
        if (!$business) return response()->json(['message' => 'Not found.'], 404);

        $business = $this->businessService->update($request->user(), $business, $request->all());
        return response()->json(['message' => 'Business updated.', 'data' => $business]);
    }
}
