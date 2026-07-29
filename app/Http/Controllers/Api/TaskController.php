<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Services\TaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    // ─── GET /api/v1/tasks ────────────────────────────────────────────────────
    /**
     * List tasks for the current user's business.
     * Query params: status, category, assigned_to
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tasks = $this->taskService->listForUser(
                $request->user(),
                $request->only('status', 'category', 'assigned_to')
            );
            return response()->json(['data' => $tasks]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    // ─── POST /api/v1/tasks ───────────────────────────────────────────────────
    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskService->create($request->user(), $request->validated());
            return response()->json(['message' => 'Task created.', 'data' => $task], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ─── GET /api/v1/tasks/{id} ───────────────────────────────────────────────
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $task = $this->taskService->findForUser($request->user(), $id);
            return response()->json(['data' => $task]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── PUT /api/v1/tasks/{id} ───────────────────────────────────────────────
    public function update(UpdateTaskRequest $request, string $id): JsonResponse
    {
        try {
            $task = $this->taskService->update($request->user(), $id, $request->validated());
            return response()->json(['message' => 'Task updated.', 'data' => $task]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── PATCH /api/v1/tasks/{id}/status ─────────────────────────────────────
    /**
     * Transition task status: pending → in_progress → completed | overdue
     */
    public function updateStatus(UpdateTaskStatusRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $task = $this->taskService->updateStatus($request->user(), $id, $validated['status']);
            return response()->json(['message' => 'Task status updated.', 'data' => $task]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── DELETE /api/v1/tasks/{id} ────────────────────────────────────────────
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->taskService->delete($request->user(), $id);
            return response()->json(['message' => 'Task deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
