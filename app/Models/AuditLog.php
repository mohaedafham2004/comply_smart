<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'audit_logs';

    /**
     * Audit logs should never be modified or mass-updated.
     */
    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'action',       // e.g. "created", "updated", "deleted", "login"
        'entity_type',  // e.g. "Document", "Task", "User"
        'entity_id',
        'changes',      // array/object of before/after values
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
