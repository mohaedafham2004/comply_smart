<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepo,
        private readonly AuditService            $auditService,
    ) {}

    // ─── Create ───────────────────────────────────────────────────────────────

    /**
     * Create a task for the authenticated user's business.
     * business_id is always taken from the actor — never from user input.
     */
    public function create(User $actor, array $data): Task
    {
        $businessId = $this->requireBusinessId($actor);

        $task = $this->taskRepo->create([
            'business_id' => $businessId,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'category'    => $data['category']    ?? 'general',
            'priority'    => $data['priority']    ?? Task::PRIORITY_MEDIUM,
            'due_date'    => !empty($data['due_date']) ? $data['due_date'] : null,
            'status'      => Task::STATUS_PENDING,
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        $this->auditService->log($actor, 'created', 'Task', (string) $task->_id, [
            'title'  => $task->title,
            'status' => $task->status,
        ]);

        return $task;
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * List all tasks for the actor's business.
     * Optional filters: status, assigned_to, category.
     */
    public function listForUser(User $actor, array $filters = []): Collection
    {
        $businessId = $this->requireBusinessId($actor);
        return $this->taskRepo->findByBusiness($businessId, $filters);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    /**
     * Find a task and verify it belongs to the actor's business.
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function findForUser(User $actor, string $id): Task
    {
        $task = $this->taskRepo->findById($id);

        if (!$task) {
            throw new \RuntimeException('Task not found.');
        }

        if ((string) $task->business_id !== $this->requireBusinessId($actor)) {
            throw new AuthorizationException('This task does not belong to your business.');
        }

        return $task;
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * Update allowed fields: title, description, category, due_date, assigned_to.
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function update(User $actor, string $id, array $data): Task
    {
        $task = $this->findForUser($actor, $id);

        $allowed = array_intersect_key($data, array_flip([
            'title', 'description', 'category', 'priority', 'due_date', 'assigned_to',
        ]));

        $this->taskRepo->update($task, $allowed);
        $task->refresh();

        $this->auditService->log($actor, 'updated', 'Task', (string) $task->_id, $allowed);

        return $task;
    }

    // ─── Status ───────────────────────────────────────────────────────────────

    /**
     * Transition a task's status.
     * Valid transitions enforced here to prevent arbitrary status hacks.
     *
     * @throws AuthorizationException|\RuntimeException|\InvalidArgumentException
     */
    public function updateStatus(User $actor, string $id, string $newStatus): Task
    {
        $validStatuses = [
            Task::STATUS_PENDING,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
            Task::STATUS_OVERDUE,
        ];

        if (!in_array($newStatus, $validStatuses, true)) {
            throw new \InvalidArgumentException(
                "Invalid status '{$newStatus}'. Allowed: " . implode(', ', $validStatuses)
            );
        }

        $task = $this->findForUser($actor, $id);

        $this->taskRepo->update($task, ['status' => $newStatus]);
        $task->refresh();

        $this->auditService->log($actor, 'status_changed', 'Task', (string) $task->_id, [
            'new_status' => $newStatus,
        ]);

        return $task;
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * @throws AuthorizationException|\RuntimeException
     */
    public function delete(User $actor, string $id): void
    {
        $task = $this->findForUser($actor, $id);
        $this->taskRepo->delete($task);
        $this->auditService->log($actor, 'deleted', 'Task', (string) $task->_id);
    }

    // ─── Scheduler helpers ────────────────────────────────────────────────────

    /**
     * Mark all pending/in-progress tasks whose due_date has passed as overdue.
     * Called by the daily scheduler.
     */
    public function markOverdueTasks(): int
    {
        return $this->taskRepo->markOverdue();
    }

    // ─── Compliance ───────────────────────────────────────────────────────────

    /**
     * Return task-based stats for compliance scoring.
     */
    public function getComplianceStats(string $businessId): array
    {
        $all       = $this->taskRepo->findByBusiness($businessId);
        $total     = $all->count();
        $overdue   = $all->where('status', Task::STATUS_OVERDUE)->count();
        $completed = $all->where('status', Task::STATUS_COMPLETED)->count();

        return compact('total', 'overdue', 'completed');
    }

    // ─── Legacy (backward compat) ─────────────────────────────────────────────

    public function findById(string $id): ?Task
    {
        return $this->taskRepo->findById($id);
    }

    public function listForBusiness(string $businessId, array $filters = []): Collection
    {
        return $this->taskRepo->findByBusiness($businessId, $filters);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function requireBusinessId(User $user): string
    {
        if (!$user->business_id) {
            throw new \RuntimeException('Your account has no linked business.');
        }
        return (string) $user->business_id;
    }
}
