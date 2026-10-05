<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Services\DocumentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/documents
     *
     * Query params:
     *   category   — filter by category slug
     *   ocr_status — filter by ocr_status (pending|processing|done|failed)
     *   search     — full-text search in title and OCR text
     *   per_page   — default 15
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only('category', 'ocr_status', 'search');
            $perPage = $request->integer('per_page', 15);
            $docs    = $this->documentService->listForUser($request->user(), $filters, $perPage);

            return response()->json([
                'data'       => $docs->items(),
                'pagination' => [
                    'current_page' => $docs->currentPage(),
                    'last_page'    => $docs->lastPage(),
                    'per_page'     => $docs->perPage(),
                    'total'        => $docs->total(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── Upload ───────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/documents
     *
     * Multipart form-data:
     *   title, category, file (pdf/jpg/png ≤10MB), expiry_date (optional)
     *
     * The authenticated user's business_id is used as the target — no businessId
     * URL param needed.
     */
    public function store(UploadDocumentRequest $request): JsonResponse
    {
        try {
            $businessId = (string) $request->user()->business_id;

            if (!$businessId) {
                return response()->json(['message' => 'Your account has no linked business.'], 422);
            }

            $document = $this->documentService->upload(
                $request->user(),
                $businessId,
                $request->validated(),
                $request->file('file'),
            );

            return response()->json([
                'message' => 'Document uploaded. OCR extraction queued.',
                'data'    => $document,
            ], 201);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Document upload error: ' . $e->getMessage());
            return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/documents/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $document = $this->documentService->findForUser($request->user(), $id);
            return response()->json(['data' => $document]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * PUT /api/v1/documents/{id}
     *
     * Updates title, category, or expiry_date only.
     */
    public function update(UpdateDocumentRequest $request, string $id): JsonResponse
    {
        try {
            $document = $this->documentService->update($request->user(), $id, $request->validated());
            return response()->json([
                'message' => 'Document updated.',
                'data'    => $document,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * DELETE /api/v1/documents/{id}
     *
     * Deletes from MongoDB and Cloudinary.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->documentService->delete($request->user(), $id);
            return response()->json(['message' => 'Document deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── OCR Status ───────────────────────────────────────────────────────────

    /**
     * GET /api/v1/documents/{id}/ocr-status
     *
     * Returns: ocr_status, ocr_extracted_text (if done), ocr_error (if failed).
     * Frontend polls this until ocr_status is 'done' or 'failed'.
     */
    public function ocrStatus(Request $request, string $id): JsonResponse
    {
        try {
            $status = $this->documentService->getOcrStatus($request->user(), $id);
            return response()->json(['data' => $status]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── Legacy nested endpoint (kept for backward compat) ───────────────────

    /**
     * GET /api/v1/businesses/{businessId}/documents
     * @deprecated Use GET /api/v1/documents instead
     */
    public function indexByBusiness(string $businessId): JsonResponse
    {
        $documents = $this->documentService->listForBusiness($businessId);
        return response()->json(['data' => $documents]);
    }

    /**
     * POST /api/v1/businesses/{businessId}/documents
     * @deprecated Use POST /api/v1/documents instead
     */
    public function storeForBusiness(UploadDocumentRequest $request, string $businessId): JsonResponse
    {
        try {
            $document = $this->documentService->upload(
                $request->user(),
                $businessId,
                $request->validated(),
                $request->file('file'),
            );
            return response()->json(['message' => 'Document uploaded.', 'data' => $document], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
