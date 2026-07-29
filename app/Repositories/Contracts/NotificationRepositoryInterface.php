<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function create(array $data): Notification;
    public function findById(string $id): ?Notification;
    public function findForUser(string $userId, bool $unreadOnly = false): Collection;
    public function markAllReadForUser(string $userId): void;
}
