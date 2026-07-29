<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\ChatAIRequest;
use App\Http\Requests\AI\ChecklistAIRequest;
use App\Http\Requests\AI\SummarizeAIRequest;
use App\Services\AIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct(private readonly AIChatService $aiChatService) {}

    // ─── POST /api/v1/ai/chat ─────────────────────────────────────────────────
    /**
     * Send a message to the AI chatbot.
     *
     * business_id is derived from the authenticated user — NOT accepted from input
     * to prevent cross-business data leakage.
     *
     * Body: { message: string }
     * Returns: { data: { reply, error } }
     */
    public function chat(ChatAIRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        if (!$user->business_id) {
            return response()->json(['message' => 'Your account has no linked business.'], 422);
        }

        try {
            $result = $this->aiChatService->chat(
                $user,
                (string) $user->business_id,
                $validated['message'],
            );

            return response()->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            // API key not configured, etc.
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    // ─── GET /api/v1/ai/chat/history ─────────────────────────────────────────
    /**
     * Retrieve the current user's chat history for their business.
     * Query param: limit (int, default 50, max 100)
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->business_id) {
            return response()->json(['data' => []]);
        }

        $limit = min((int) $request->query('limit', 50), 100);

        $history = $this->aiChatService->getHistory(
            (string) $user->_id,
            (string) $user->business_id,
            $limit,
        );

        return response()->json(['data' => $history]);
    }

    // ─── POST /api/v1/ai/checklist ────────────────────────────────────────────
    /**
     * Generate a compliance checklist via Gemini.
     *
     * Body: {
     *   business_type: string (required),
     *   description:   string (optional — extra context),
     *   location:      string (optional, default "Sri Lanka")
     * }
     *
     * Returns: {
     *   data: {
     *     items: [{ title, category, description, priority, suggested_due_in_days }],
     *     error: bool,
     *     error_message?: string
     *   }
     * }
     */
    public function generateChecklist(ChecklistAIRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->aiChatService->generateChecklist(
                $validated['business_type'],
                $validated['description'] ?? '',
                $validated['location']    ?? 'Sri Lanka',
            );

            $status = $result['error'] ? 502 : 200;
            return response()->json(['data' => $result], $status);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    // ─── POST /api/v1/ai/summarize ────────────────────────────────────────────
    /**
     * Summarize a regulation or document.
     *
     * Body (either one required):
     *   - document_id: string — pull OCR text from an existing document
     *   - text:        string — raw regulation text (up to 12 000 chars)
     *
     * Returns: {
     *   data: { summary: string, error: bool, error_message?: string }
     * }
     */
    public function summarize(SummarizeAIRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (empty($validated['document_id']) && empty($validated['text'])) {
            return response()->json([
                'message' => 'Provide either a document_id or raw text to summarize.',
            ], 422);
        }

        // Ownership check when summarizing by document_id
        if (!empty($validated['document_id'])) {
            $user     = $request->user();
            $document = \App\Models\Document::find($validated['document_id']);
            if (!$document) {
                return response()->json(['message' => 'Document not found.'], 404);
            }
            if ($user->business_id && (string) $document->business_id !== (string) $user->business_id) {
                return response()->json(['message' => 'Access denied to this document.'], 403);
            }
        }

        try {
            $result = $this->aiChatService->summarize(
                $validated['text']        ?? '',
                $validated['document_id'] ?? null,
            );

            $status = $result['error'] ? 502 : 200;
            return response()->json(['data' => $result], $status);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}
