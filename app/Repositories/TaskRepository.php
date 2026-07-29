<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function findById(string $id): ?Task
    {
        return Task::find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): Collection
    {
        $query = Task::where('business_id', $businessId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->latest()->get();
    }

    public function update(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function markOverdue(): int
    {
        return Task::whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS])
            ->where('due_date', '<', now())
            ->update(['status' => Task::STATUS_OVERDUE]);
    }
}
