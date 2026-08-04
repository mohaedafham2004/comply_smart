<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly BusinessService $businessService,
    ) {}

    /**
     * GET /api/v1/reports/overview
     *
     * Returns compliance overview metrics for current user's business.
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $report = $this->reportService->overview($request->user());
            return response()->json(['data' => $report]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * GET /api/v1/reports/audit-log
     *
     * Returns paginated system audit logs (Admin restricted).
     * Query params: user_id, action, entity_type, date_from, date_to, per_page
     */
    public function auditLog(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);
        $filters = $request->only('user_id', 'action', 'entity_type', 'date_from', 'date_to');

        $logs = $this->reportService->auditLog($filters, $perPage);
        return response()->json(['data' => $logs]);
    }

    /**
     * Legacy GET /api/v1/businesses/{id}/report/compliance
     */
    public function complianceSummary(string $id): JsonResponse
    {
        $business = $this->businessService->findById($id);
        if (!$business) return response()->json(['message' => 'Business not found.'], 404);

        // Fetch using existing service logic
        $tasks          = \App\Models\Task::where('business_id', $business->_id)->get();
        $totalTasks     = $tasks->count();
        $overdueTasks   = $tasks->where('status', \App\Models\Task::STATUS_OVERDUE)->count();
        $completedTasks = $tasks->where('status', \App\Models\Task::STATUS_COMPLETED)->count();

        $totalDocs   = \App\Models\Document::where('business_id', $business->_id)->count();
        $upcomingRen = \App\Models\Renewal::where('business_id', $business->_id)
            ->where('due_date', '<=', now()->addDays(30))
            ->where('status', '!=', \App\Models\Renewal::STATUS_COMPLETED)
            ->count();

        return response()->json([
            'data' => [
                'business'   => $business->only('_id', 'name', 'type', 'compliance_score'),
                'tasks'      => ['total' => $totalTasks, 'completed' => $completedTasks, 'overdue' => $overdueTasks],
                'documents'  => ['total' => $totalDocs],
                'renewals'   => ['upcoming_30_days' => $upcomingRen],
                'generated_at' => now()->toIso8601String(),
            ]
        ]);
    }
}
