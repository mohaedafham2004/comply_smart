<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Repositories\Contracts\BusinessRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;

class BusinessService
{
    public function __construct(
        private readonly BusinessRepositoryInterface $businessRepo,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get the business profile for the authenticated user.
     * Staff members see their employer's business; owners see their own.
     *
     * @throws \RuntimeException if user has no business linked
     */
    public function getForUser(User $user): Business
    {
        $businessId = $user->business_id;

        if (!$businessId) {
            throw new \RuntimeException('No business linked to this account.');
        }

        $business = $this->businessRepo->findById($businessId);

        if (!$business) {
            throw new \RuntimeException('Business not found.');
        }

        return $business;
    }

    /**
     * Update the business profile.
     * Only the business owner or a system admin may do this.
     *
     * Allowed fields: name, type, registration_no, address, phone, email
     * Forbidden: owner_id, compliance_score (managed internally)
     *
     * @throws AuthorizationException
     */
    public function updateForUser(User $actor, array $data): Business
    {
        $business = $this->getForUser($actor);

        // Ownership/admin gate
        if ($actor->role !== 'admin' && (string) $business->owner_id !== (string) $actor->_id) {
            throw new AuthorizationException('Only the business owner or an admin can update the business profile.');
        }

        // Strip any fields that must not be user-modifiable
        $allowed = array_intersect_key($data, array_flip([
            'name', 'type', 'registration_no', 'address', 'phone', 'email',
        ]));

        $before = $business->only(array_keys($allowed));
        $this->businessRepo->update($business, $allowed);
        $business->refresh();

        $this->auditService->log($actor, 'updated', 'Business', (string) $business->_id, [
            'before' => $before,
            'after'  => $business->only(array_keys($allowed)),
        ]);

        return $business;
    }

    /**
     * Create a new business (used during registration flow).
     */
    public function create(User $actor, array $data): Business
    {
        $business = $this->businessRepo->create([
            'name'            => $data['name'],
            'type'            => $data['type'],
            'registration_no' => $data['registration_no'] ?? null,
            'owner_id'        => (string) $actor->_id,
            'address'         => $data['address'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'email'           => $data['email'] ?? null,
            'compliance_score'=> 0.0,
        ]);

        $this->auditService->log($actor, 'created', 'Business', (string) $business->_id, [
            'name' => $business->name,
        ]);

        return $business;
    }

    /**
     * Update a business by ID (admin or general purpose).
     */
    public function update(User $actor, Business $business, array $data): Business
    {
        $allowed = array_intersect_key($data, array_flip([
            'name', 'type', 'registration_no', 'address', 'phone', 'email',
        ]));

        $before = $business->only(array_keys($allowed));
        $this->businessRepo->update($business, $allowed);
        $business->refresh();

        $this->auditService->log($actor, 'updated', 'Business', (string) $business->_id, [
            'before' => $before,
            'after'  => $business->only(array_keys($allowed)),
        ]);

        return $business;
    }

    /**
     * Calculate compliance score from tasks + renewals.
     *
     * Formula (0–100):
     *   - Tasks component (50%):   max(0, 50 - (overdue_tasks / total_tasks) * 50)
     *   - Renewals component (50%): max(0, 50 - (overdue_renewals / total_renewals) * 50)
     *   - If no tasks/renewals exist that sub-score is full (50).
     *
     * Score is persisted back to the business record.
     */
    public function recalculateComplianceScore(Business $business): float
    {
        $businessId = (string) $business->_id;

        // Tasks component
        $tasks          = \App\Models\Task::where('business_id', $businessId)->get();
        $totalTasks     = $tasks->count();
        $overdueTasks   = $tasks->where('status', \App\Models\Task::STATUS_OVERDUE)->count();
        $taskScore      = $totalTasks > 0
            ? max(0.0, 50.0 - ($overdueTasks / $totalTasks) * 50.0)
            : 50.0;

        // Renewals component
        $renewals        = \App\Models\Renewal::where('business_id', $businessId)->get();
        $totalRenewals   = $renewals->count();
        $overdueRenewals = $renewals->where('status', \App\Models\Renewal::STATUS_OVERDUE)->count();
        $renewalScore    = $totalRenewals > 0
            ? max(0.0, 50.0 - ($overdueRenewals / $totalRenewals) * 50.0)
            : 50.0;

        $score = round($taskScore + $renewalScore, 1);

        $this->businessRepo->update($business, ['compliance_score' => $score]);

        return $score;
    }

    /**
     * Return full compliance breakdown for the GET /api/v1/business/compliance-score endpoint.
     */
    public function getComplianceScore(User $user): array
    {
        $business   = $this->getForUser($user);
        $businessId = (string) $business->_id;

        // Tasks
        $tasks          = \App\Models\Task::where('business_id', $businessId)->get();
        $totalTasks     = $tasks->count();
        $completedTasks = $tasks->where('status', \App\Models\Task::STATUS_COMPLETED)->count();
        $overdueTasks   = $tasks->where('status', \App\Models\Task::STATUS_OVERDUE)->count();
        $pendingTasks   = $tasks->whereIn('status', [\App\Models\Task::STATUS_PENDING, \App\Models\Task::STATUS_IN_PROGRESS])->count();

        // Renewals
        $renewals        = \App\Models\Renewal::where('business_id', $businessId)->get();
        $totalRenewals   = $renewals->count();
        $overdueRenewals = $renewals->where('status', \App\Models\Renewal::STATUS_OVERDUE)->count();
        $dueRenewals     = $renewals->where('status', \App\Models\Renewal::STATUS_DUE)->count();

        // Upcoming (next 30 days)
        $upcomingRenewals = \App\Models\Renewal::where('business_id', $businessId)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->where('status', '!=', \App\Models\Renewal::STATUS_COMPLETED)
            ->count();

        // Score
        $score = $this->recalculateComplianceScore($business);

        return [
            'compliance_score' => $score,
            'business_id'      => $businessId,
            'tasks'            => [
                'total'     => $totalTasks,
                'completed' => $completedTasks,
                'overdue'   => $overdueTasks,
                'pending'   => $pendingTasks,
            ],
            'renewals'         => [
                'total'    => $totalRenewals,
                'overdue'  => $overdueRenewals,
                'due_soon' => $dueRenewals + $upcomingRenewals,
            ],
            'score_breakdown'  => [
                'tasks_score'    => $totalTasks > 0
                    ? max(0.0, round(50.0 - ($overdueTasks / $totalTasks) * 50.0, 1))
                    : 50.0,
                'renewals_score' => $totalRenewals > 0
                    ? max(0.0, round(50.0 - ($overdueRenewals / $totalRenewals) * 50.0, 1))
                    : 50.0,
            ],
        ];
    }


    /**
     * List businesses accessible to the given user.
     */
    public function listForUser(User $user): \Illuminate\Support\Collection
    {
        if ($user->role === 'admin') {
            return $this->businessRepo->all();
        }
        return $this->businessRepo->findByOwner((string) $user->_id);
    }

    /**
     * Get a single business by ID.
     */
    public function findById(string $id): ?Business
    {
        return $this->businessRepo->findById($id);
    }
}
