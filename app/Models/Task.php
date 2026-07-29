<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Task extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'tasks';

    /**
     * Valid status values.
     */
    const STATUS_PENDING     = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_OVERDUE     = 'overdue';

    protected $fillable = [
        'business_id',
        'title',
        'description',
        'category',
        'due_date',
        'status',       // pending | in_progress | completed | overdue
        'assigned_to',  // user_id (nullable)
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
