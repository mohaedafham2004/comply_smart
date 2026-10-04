<?php

namespace App\Repositories;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function findById(string $id): ?Document
    {
        return Document::find($id);
    }

    public function findByBusiness(string $businessId): Collection
    {
        return Document::where('business_id', $businessId)->latest()->get();
    }

    /**
     * Paginated list with optional category + text search filters.
     */
    public function findByBusinessPaginated(
        string $businessId,
        array  $filters = [],
        int    $perPage = 15,
    ): LengthAwarePaginator {
        $query = Document::where('business_id', $businessId);

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['ocr_status'])) {
            $query->where('ocr_status', $filters['ocr_status']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('title', 'regexp', "/{$term}/i")
                  ->orWhere('ocr_extracted_text', 'regexp', "/{$term}/i");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function update(Document $document, array $data): bool
    {
        return $document->update($data);
    }

    public function delete(Document $document): bool
    {
        return $document->delete();
    }
}
