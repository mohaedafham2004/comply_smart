<?php

namespace App\Repositories\Contracts;

use App\Models\Renewal;
use Illuminate\Support\Collection;

interface RenewalRepositoryInterface
{
    public function create(array $data): Renewal;
    public function findById(string $id): ?Renewal;
    public function findByBusiness(string $businessId, array $filters = []): Collection;
    public function findUpcomingForBusiness(string $businessId, int $days): Collection;
    public function findDueSoon(int $daysAhead): Collection;
    public function update(Renewal $renewal, array $data): bool;
    public function delete(Renewal $renewal): bool;
}
