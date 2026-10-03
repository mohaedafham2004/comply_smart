<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Business;
use App\Models\Document;
use App\Models\Task;
use App\Models\Renewal;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\AiChatHistory;

class ResetDatabase extends Command
{
    protected $signature = 'db:wipe-complysmart';
    protected $description = 'Wipe all ComplySmart collections for a clean slate';

    public function handle(): int
    {
        $this->info('Wiping all ComplySmart collections...');

        User::truncate();
        Business::truncate();
        Document::truncate();
        Task::truncate();
        Renewal::truncate();
        Notification::truncate();
        AuditLog::truncate();
        AiChatHistory::truncate();

        $this->info('All collections wiped successfully.');
        return 0;
    }
}
