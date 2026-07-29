<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    // ─── GET /api/v1/notifications ────────────────────────────────────────────
    /**
     * List notifications for the authenticated user.
     * Query param: unread_only=1
     */
    public function index(Request $request): JsonResponse
    {
        $unreadOnly    = $request->boolean('unread_only', false);
        $notifications = $this->notificationService->listForUser(
            (string) $request->user()->_id,
            $unreadOnly
        );

        return response()->json([
            'data' => $notifications,
            'meta' => [
                'total'       => $notifications->count(),
                'unread'      => $notifications->whereNull('read_at')->count(),
            ],
        ]);
    }

    // ─── PATCH /api/v1/notifications/{id}/read ────────────────────────────────
    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markAsRead($id, $request->user());
        return response()->json(['message' => 'Marked as read.']);
    }

    // ─── PATCH /api/v1/notifications/read-all ────────────────────────────────
    /**
     * Mark ALL unread notifications as read for this user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());
        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
