<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DocumentRepositoryInterface
{
    public function create(array $data): Document;
    public function findById(string $id): ?Document;
    public function findByBusiness(string $businessId): Collection;

    /**
     * Paginated + filterable list for a business.
     *
     * @param  array{category?: string, search?: string}  $filters
     */
    public function findByBusinessPaginated(
        string $businessId,
        array  $filters = [],
        int    $perPage = 15,
    ): LengthAwarePaginator;

    public function update(Document $document, array $data): bool;
    public function delete(Document $document): bool;
}
