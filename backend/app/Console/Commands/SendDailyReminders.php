<?php

namespace App\Console\Commands;

use App\Services\RenewalService;
use App\Services\TaskService;
use Illuminate\Console\Command;

/**
 * SendDailyReminders
 *
 * ─── Scheduled (daily at 08:00) ──────────────────────────────────────────────
 *   Registered in bootstrap/app.php via $schedule->command('complysmart:send-reminders')->dailyAt('08:00')
 *
 * ─── Manual trigger (for testing without waiting a full day) ─────────────────
 *   php artisan complysmart:send-reminders
 *   php artisan complysmart:send-reminders --days=7   # only 7-day window
 *
 * ─── What it does ─────────────────────────────────────────────────────────────
 *   1. Marks overdue tasks (status → overdue if due_date < now)
 *   2. Finds renewals due within --days (default 30) where reminder_sent_at is null
 *   3. Creates in-app notifications for all business users
 *   4. Sends email reminder to business owner
 *   5. Stamps reminder_sent_at to prevent duplicate sends
 */
class SendDailyReminders extends Command
{
    protected $signature = 'complysmart:send-reminders
                            {--days=30 : How many days ahead to look for due renewals}
                            {--dry-run : Simulate without sending anything}';

    protected $description = 'Send renewal/task reminder emails and create in-app notifications';

    public function handle(
        RenewalService $renewalService,
        TaskService    $taskService,
    ): int {
        $days   = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('ComplySmart Daily Reminder');
        $this->info('──────────────────────────');
        $this->line("  Days window : {$days}");
        $this->line('  Dry run     : ' . ($dryRun ? 'YES (nothing will be sent)' : 'NO'));
        $this->newLine();

        // ── 1. Mark overdue tasks ──────────────────────────────────────────────
        if (!$dryRun) {
            $overdueTasks = $taskService->markOverdueTasks();
            $this->line("  Marked overdue tasks : <fg=yellow>{$overdueTasks}</>");
        } else {
            $this->line('  [dry-run] Would mark overdue tasks.');
        }

        // ── 2. Send renewal reminders ─────────────────────────────────────────
        if (!$dryRun) {
            $remindersSent = $renewalService->sendDueReminders($days);
            $this->line("  Renewal reminders sent : <fg=green>{$remindersSent}</>");
        } else {
            // Count without sending
            $count = \App\Models\Renewal::where('due_date', '<=', now()->addDays($days))
                ->where('due_date', '>=', now())
                ->where('status', '!=', \App\Models\Renewal::STATUS_COMPLETED)
                ->whereNull('reminder_sent_at')
                ->count();
            $this->line("  [dry-run] Would send {$count} reminder(s).");
        }

        $this->newLine();
        $this->info('Done. Check storage/logs/laravel.log if MAIL_MAILER=log.');

        return self::SUCCESS;
    }
}
