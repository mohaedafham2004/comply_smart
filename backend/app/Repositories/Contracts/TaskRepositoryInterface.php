<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    public function create(array $data): Task;
    public function findById(string $id): ?Task;
    public function findByBusiness(string $businessId, array $filters = []): Collection;
    public function update(Task $task, array $data): bool;
    public function delete(Task $task): bool;
    /** Mark all past-due pending/in_progress tasks as overdue. Returns count updated. */
    public function markOverdue(): int;
}
