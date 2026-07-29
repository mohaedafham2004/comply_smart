<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog;
    public function findByEntity(string $entityType, string $entityId): Collection;
    public function findByUser(string $userId): Collection;
}
