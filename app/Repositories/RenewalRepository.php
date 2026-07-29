<?php

namespace App\Repositories;

use App\Models\Renewal;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Illuminate\Support\Collection;

class RenewalRepository implements RenewalRepositoryInterface
{
    public function create(array $data): Renewal
    {
        return Renewal::create($data);
    }

    public function findById(string $id): ?Renewal
    {
        return Renewal::find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): Collection
    {
        $query = Renewal::where('business_id', $businessId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('due_date')->get();
    }

    /**
     * Renewals due within the next $days days for a specific business.
     * Excludes already-completed renewals.
     */
    public function findUpcomingForBusiness(string $businessId, int $days): Collection
    {
        return Renewal::where('business_id', $businessId)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->where('status', '!=', Renewal::STATUS_COMPLETED)
            ->orderBy('due_date')
            ->get();
    }

    /**
     * All businesses: renewals due soon (used by the global reminder scheduler).
     */
    public function findDueSoon(int $daysAhead): Collection
    {
        return Renewal::where('due_date', '<=', now()->addDays($daysAhead))
            ->where('due_date', '>=', now())
            ->where('status', '!=', Renewal::STATUS_COMPLETED)
            ->whereNull('reminder_sent_at')
            ->get();
    }

    public function update(Renewal $renewal, array $data): bool
    {
        return $renewal->update($data);
    }

    public function delete(Renewal $renewal): bool
    {
        return $renewal->delete();
    }
}
