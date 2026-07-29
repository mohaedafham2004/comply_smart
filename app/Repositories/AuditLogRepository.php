<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Collection;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function findByEntity(string $entityType, string $entityId): Collection
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->latest()
            ->get();
    }

    public function findByUser(string $userId): Collection
    {
        return AuditLog::where('user_id', $userId)->latest()->get();
    }
}
