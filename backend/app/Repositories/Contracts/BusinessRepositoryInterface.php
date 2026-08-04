<?php

namespace App\Repositories\Contracts;

use App\Models\Business;
use Illuminate\Support\Collection;

interface BusinessRepositoryInterface
{
    public function create(array $data): Business;
    public function findById(string $id): ?Business;
    public function findByOwner(string $ownerId): Collection;
    public function update(Business $business, array $data): bool;
    public function delete(Business $business): bool;
    public function all(): Collection;
}
