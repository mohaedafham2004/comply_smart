<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AiChatHistory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'ai_chat_history';

    const ROLE_USER      = 'user';
    const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'user_id',
        'business_id',
        'role',    // user | assistant
        'message',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
