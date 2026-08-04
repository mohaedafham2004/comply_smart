<?php

namespace App\Repositories;

use App\Models\Business;
use App\Repositories\Contracts\BusinessRepositoryInterface;
use Illuminate\Support\Collection;

class BusinessRepository implements BusinessRepositoryInterface
{
    public function create(array $data): Business
    {
        return Business::create($data);
    }

    public function findById(string $id): ?Business
    {
        return Business::find($id);
    }

    public function findByOwner(string $ownerId): Collection
    {
        return Business::where('owner_id', $ownerId)->get();
    }

    public function update(Business $business, array $data): bool
    {
        return $business->update($data);
    }

    public function delete(Business $business): bool
    {
        return $business->delete();
    }

    public function all(): Collection
    {
        return Business::all();
    }
}
