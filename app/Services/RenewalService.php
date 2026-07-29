<?php

namespace App\Services;

use App\Models\Renewal;
use App\Models\User;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReminderMail;

class RenewalService
{
    public function __construct(
        private readonly RenewalRepositoryInterface $renewalRepo,
        private readonly NotificationService        $notificationService,
        private readonly AuditService               $auditService,
    ) {}

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(User $actor, array $data): Renewal
    {
        $businessId = $this->requireBusinessId($actor);

        $renewal = $this->renewalRepo->create([
            'business_id'  => $businessId,
            'document_id'  => $data['document_id']  ?? null,
            'title'        => $data['title'],
            'renewal_type' => $data['renewal_type'],
            'due_date'     => $data['due_date'],
            'status'       => Renewal::STATUS_UPCOMING,
            'notes'        => $data['notes'] ?? null,
        ]);

        $this->auditService->log($actor, 'created', 'Renewal', (string) $renewal->_id, [
            'title'    => $renewal->title,
            'due_date' => $renewal->due_date?->toDateString(),
        ]);

        return $renewal;
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * List renewals for the actor's business, optionally filtered by status.
     */
    public function listForUser(User $actor, array $filters = []): Collection
    {
        $businessId = $this->requireBusinessId($actor);
        return $this->renewalRepo->findByBusiness($businessId, $filters);
    }

    /**
     * Renewals due within the next $days days for the actor's business.
     */
    public function upcomingForUser(User $actor, int $days = 30): Collection
    {
        $businessId = $this->requireBusinessId($actor);
        return $this->renewalRepo->findUpcomingForBusiness($businessId, $days);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    /**
     * @throws AuthorizationException|\RuntimeException
     */
    public function findForUser(User $actor, string $id): Renewal
    {
        $renewal = $this->renewalRepo->findById($id);

        if (!$renewal) {
            throw new \RuntimeException('Renewal not found.');
        }

        if ((string) $renewal->business_id !== $this->requireBusinessId($actor)) {
            throw new AuthorizationException('This renewal does not belong to your business.');
        }

        return $renewal;
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * @throws AuthorizationException|\RuntimeException
     */
    public function update(User $actor, string $id, array $data): Renewal
    {
        $renewal = $this->findForUser($actor, $id);

        $allowed = array_intersect_key($data, array_flip([
            'title', 'renewal_type', 'due_date', 'status', 'document_id', 'notes',
        ]));

        $this->renewalRepo->update($renewal, $allowed);
        $renewal->refresh();

        $this->auditService->log($actor, 'updated', 'Renewal', (string) $renewal->_id, $allowed);

        return $renewal;
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * @throws AuthorizationException|\RuntimeException
     */
    public function delete(User $actor, string $id): void
    {
        $renewal = $this->findForUser($actor, $id);
        $this->renewalRepo->delete($renewal);
        $this->auditService->log($actor, 'deleted', 'Renewal', (string) $renewal->_id);
    }

    // ─── Scheduler ────────────────────────────────────────────────────────────

    /**
     * Send email reminders for all renewals due within $daysAhead.
     * Creates in-app notifications AND sends email to business owner.
     * Records reminder_sent_at to avoid duplicates.
     *
     * @return int  Number of reminders sent
     */
    public function sendDueReminders(int $daysAhead = 30): int
    {
        $renewals = $this->renewalRepo->findDueSoon($daysAhead);
        $count    = 0;

        foreach ($renewals as $renewal) {
            // createForBusiness creates in-app notifications for all business users
            $this->notificationService->createForBusiness(
                (string) $renewal->business_id,
                'renewal_reminder',
                "Renewal Due: {$renewal->title}",
                "Your '{$renewal->title}' renewal is due on " .
                    $renewal->due_date->format('d M Y') . '.',
            );

            // Send email to business owner
            $owner = \App\Models\User::where('business_id', $renewal->business_id)
                ->where('role', 'owner')
                ->first();

            if ($owner) {
                try {
                    Mail::to($owner->email)->send(new ReminderMail(
                        subject:     "Reminder: {$renewal->title} is due soon",
                        heading:     'Renewal Reminder',
                        bodyLines:   [
                            "Hi {$owner->name},",
                            "This is a reminder that your **{$renewal->title}** ({$renewal->renewal_type}) is due on **{$renewal->due_date->format('d M Y')}**.",
                            'Please log in to ComplySmart to take action.',
                        ],
                        actionUrl:   config('app.url') . '/test/renewals',
                        actionText:  'View Renewals',
                    ));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        "Failed to send renewal reminder email to {$owner->email}: " . $e->getMessage()
                    );
                }
            }

            $this->renewalRepo->update($renewal, ['reminder_sent_at' => now()]);
            $count++;
        }

        return $count;
    }

    // ─── Compliance ───────────────────────────────────────────────────────────

    /**
     * Return renewal-based stats for compliance scoring.
     */
    public function getComplianceStats(string $businessId): array
    {
        $all     = $this->renewalRepo->findByBusiness($businessId);
        $total   = $all->count();
        $overdue = $all->where('status', Renewal::STATUS_OVERDUE)->count();

        return compact('total', 'overdue');
    }

    // ─── Legacy ───────────────────────────────────────────────────────────────

    public function findById(string $id): ?Renewal
    {
        return $this->renewalRepo->findById($id);
    }

    public function listForBusiness(string $businessId): Collection
    {
        return $this->renewalRepo->findByBusiness($businessId);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function requireBusinessId(User $user): string
    {
        if (!$user->business_id) {
            throw new \RuntimeException('Your account has no linked business.');
        }
        return (string) $user->business_id;
    }
}
