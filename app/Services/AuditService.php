<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;

class AuditService
{
    /**
     * Log a mutation event to the audit_logs collection.
     *
     * @param  User|null  $actor   The user performing the action (null for system events).
     * @param  string     $action  e.g. "created", "updated", "deleted", "login"
     * @param  string     $entityType  e.g. "Document", "Task"
     * @param  string|null $entityId   MongoDB _id of the affected document.
     * @param  array      $changes  Before/after diff, optional.
     */
    public function log(
        ?User $actor,
        string $action,
        string $entityType,
        ?string $entityId = null,
        array $changes = [],
    ): AuditLog {
        return AuditLog::create([
            'user_id'     => $actor?->_id,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'changes'     => $changes,
            'ip_address'  => request()->ip(),
        ]);
    }
}
