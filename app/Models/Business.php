<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Business extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'businesses';

    protected $fillable = [
        'name',
        'type',              // e.g. "retail", "restaurant", "it_services"
        'registration_no',
        'owner_id',
        'address',
        'phone',
        'email',
        'compliance_score',  // 0-100 float
    ];

    protected $casts = [
        'compliance_score' => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'business_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'business_id');
    }

    public function renewals()
    {
        return $this->hasMany(Renewal::class, 'business_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'business_id');
    }
}
