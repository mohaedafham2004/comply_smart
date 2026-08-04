<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Renewal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function __construct(
        private readonly BusinessService $businessService
    ) {}

    /**
     * Get high-level compliance overview dashboard report for the current user's business.
     */
    public function overview(User $user): array
    {
        $business   = $this->businessService->getForUser($user);
        $businessId = (string) $business->_id;

        // Documents
        $totalDocuments = Document::where('business_id', $businessId)->count();
        $expiringDocs   = Document::where('business_id', $businessId)
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->count();
        $expiredDocs    = Document::where('business_id', $businessId)
            ->where('expiry_date', '<', now())
            ->count();

        // Tasks
        $tasks          = Task::where('business_id', $businessId)->get();
        $totalTasks     = $tasks->count();
        $pendingTasks   = $tasks->where('status', Task::STATUS_PENDING)->count();
        $inProgress     = $tasks->where('status', Task::STATUS_IN_PROGRESS)->count();
        $completedTasks = $tasks->where('status', Task::STATUS_COMPLETED)->count();
        $overdueTasks   = $tasks->where('status', Task::STATUS_OVERDUE)->count();

        // Renewals
        $renewals        = Renewal::where('business_id', $businessId)->get();
        $totalRenewals   = $renewals->count();
        $upcomingRenewals= Renewal::where('business_id', $businessId)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->where('status', '!=', Renewal::STATUS_COMPLETED)
            ->count();
        $overdueRenewals = $renewals->where('status', Renewal::STATUS_OVERDUE)->count();
        $completedRenew  = $renewals->where('status', Renewal::STATUS_COMPLETED)->count();

        // Live Compliance Score
        $scoreData = $this->businessService->getComplianceScore($user);

        // Compliance Score Trend (simulated historical snapshot / status weighting)
        $taskCompletionRate    = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 100.0;
        $renewalCompletionRate = $totalRenewals > 0 ? round(($completedRenew / $totalRenewals) * 100, 1) : 100.0;

        return [
            'business' => [
                'id'               => $businessId,
                'name'             => $business->name,
                'type'             => $business->type,
                'compliance_score' => $scoreData['compliance_score'],
            ],
            'compliance_score_trend' => [
                'current_score'          => $scoreData['compliance_score'],
                'tasks_component_score'  => $scoreData['score_breakdown']['tasks_score'],
                'renewals_component_score'=> $scoreData['score_breakdown']['renewals_score'],
                'task_completion_rate'   => $taskCompletionRate,
                'renewal_completion_rate'=> $renewalCompletionRate,
            ],
            'documents' => [
                'total'            => $totalDocuments,
                'expiring_soon_30' => $expiringDocs,
                'expired'          => $expiredDocs,
            ],
            'tasks' => [
                'total'       => $totalTasks,
                'pending'     => $pendingTasks,
                'in_progress' => $inProgress,
                'completed'   => $completedTasks,
                'overdue'     => $overdueTasks,
            ],
            'renewals' => [
                'total'       => $totalRenewals,
                'upcoming_30' => $upcomingRenewals,
                'overdue'     => $overdueRenewals,
                'completed'   => $completedRenew,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get paginated audit trail for admin panel.
     * Filterable by user_id, action, entity_type, and date range.
     */
    public function auditLog(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }
}
