<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Renewal extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'renewals';

    const STATUS_UPCOMING  = 'upcoming';
    const STATUS_DUE       = 'due';
    const STATUS_OVERDUE   = 'overdue';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'business_id',
        'document_id',       // nullable — renewal may not be tied to a document
        'title',
        'renewal_type',      // e.g. "business_registration", "health_permit"
        'due_date',
        'reminder_sent_at',  // nullable
        'status',
    ];

    protected $casts = [
        'due_date'         => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
