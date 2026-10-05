<?php

namespace App\Services;

use App\Jobs\ExtractOcrTextJob;
use App\Models\Document;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Cloudinary\Cloudinary as CloudinarySDK;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepositoryInterface $documentRepo,
        private readonly AuditService               $auditService,
    ) {}

    // ─── Upload ───────────────────────────────────────────────────────────────

    /**
     * Upload a document to Cloudinary, persist metadata, dispatch OCR job.
     *
     * Business ownership is verified: the actor's business_id must match
     * the target businessId.
     *
     * @throws AuthorizationException
     */
    public function upload(User $actor, string $businessId, array $data, UploadedFile $file): Document
    {
        $this->assertBusinessOwnership($actor, $businessId);

        // ── Cloudinary upload (with local storage fallback) ───────────────────
        $cloudName     = config('cloudinary.cloud_name') ?: env('CLOUDINARY_CLOUD_NAME');
        $apiKey        = config('cloudinary.api_key') ?: env('CLOUDINARY_API_KEY');
        $hasCloudinary = !empty($cloudName) && !empty($apiKey) && $apiKey !== 'API_KEY' && $cloudName !== 'CLOUD_NAME';

        $secureUrl = null;
        $publicId  = null;

        if ($hasCloudinary) {
            try {
                /** @var CloudinarySDK $cloudinary */
                $cloudinary   = app(CloudinarySDK::class);
                $uploadResult = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                    'folder'          => "complysmart/{$businessId}",
                    'resource_type'   => 'auto',
                    'use_filename'    => true,
                    'unique_filename' => true,
                    'tags'            => ['complysmart', $businessId],
                ]);
                $secureUrl = $uploadResult['secure_url'] ?? null;
                $publicId  = $uploadResult['public_id'] ?? null;
            } catch (\Throwable $e) {
                Log::warning("Cloudinary upload failed ({$e->getMessage()}). Storing file locally.");
            }
        }

        // Local storage fallback if Cloudinary is not configured or failed
        if (!$secureUrl) {
            $ext      = $file->getClientOriginalExtension() ?: 'bin';
            $filename = uniqid('doc_', true) . '.' . $ext;
            $path     = $file->storeAs("documents/{$businessId}", $filename, 'public');

            $appUrl    = rtrim(config('app.url') ?: 'http://localhost:8000', '/');
            $secureUrl = "{$appUrl}/storage/{$path}";
            $publicId  = "local:{$path}";
        }

        $mimeType   = $file->getMimeType();
        $ocrStatus  = Document::supportsOcr($mimeType)
            ? Document::OCR_PENDING
            : Document::OCR_SKIPPED;

        // ── Persist document record ───────────────────────────────────────────
        $document = $this->documentRepo->create([
            'business_id'          => $businessId,
            'title'                => $data['title'],
            'category'             => $data['category'],
            'cloudinary_url'       => $secureUrl,
            'cloudinary_public_id' => $publicId,
            'file_size'            => $file->getSize(),
            'mime_type'            => $mimeType,
            'original_filename'    => $file->getClientOriginalName(),
            'ocr_status'           => $ocrStatus,
            'ocr_extracted_text'   => null,
            'uploaded_by'          => (string) $actor->_id,
            'expiry_date'          => $data['expiry_date'] ?? null,
        ]);

        // ── Dispatch OCR job (only for supported types) ───────────────────────
        if ($ocrStatus === Document::OCR_PENDING) {
            ExtractOcrTextJob::dispatch((string) $document->_id);
        }

        $this->auditService->log($actor, 'uploaded', 'Document', (string) $document->_id, [
            'title'    => $document->title,
            'category' => $document->category,
            'size'     => $document->file_size,
        ]);

        return $document;
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * Paginated document list for the actor's own business.
     * Optionally filtered by category, ocr_status, or search term.
     *
     * @throws AuthorizationException
     */
    public function listForUser(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $businessId = $this->requireBusinessId($actor);
        return $this->documentRepo->findByBusinessPaginated($businessId, $filters, $perPage);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    /**
     * Find a document and verify it belongs to the actor's business.
     *
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function findForUser(User $actor, string $id): Document
    {
        $document = $this->documentRepo->findById($id);

        if (!$document) {
            throw new \RuntimeException('Document not found.');
        }

        if ((string) $document->business_id !== $this->requireBusinessId($actor)) {
            throw new AuthorizationException('This document does not belong to your business.');
        }

        return $document;
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * Update allowed metadata fields (title, category, expiry_date).
     * Ownership enforced; cloudinary fields are never updated here.
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function update(User $actor, string $id, array $data): Document
    {
        $document = $this->findForUser($actor, $id);

        $allowed = array_intersect_key($data, array_flip(['title', 'category', 'expiry_date']));

        $this->documentRepo->update($document, $allowed);
        $document->refresh();

        $this->auditService->log($actor, 'updated', 'Document', (string) $document->_id, $allowed);

        return $document;
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * Delete document from MongoDB AND Cloudinary.
     * Ownership enforced.
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function delete(User $actor, string $id): void
    {
        $document = $this->findForUser($actor, $id);

        // Delete from Cloudinary or local storage (non-fatal if it fails)
        try {
            if ($document->cloudinary_public_id) {
                if (str_starts_with($document->cloudinary_public_id, 'local:')) {
                    $localPath = substr($document->cloudinary_public_id, 6);
                    @unlink(storage_path("app/public/{$localPath}"));
                } else {
                    /** @var CloudinarySDK $cloudinary */
                    $cloudinary = app(CloudinarySDK::class);
                    $cloudinary->uploadApi()->destroy($document->cloudinary_public_id);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("File delete failed for {$document->cloudinary_public_id}: {$e->getMessage()}");
        }

        $this->documentRepo->delete($document);

        $this->auditService->log($actor, 'deleted', 'Document', (string) $document->_id, [
            'title' => $document->title,
        ]);
    }

    // ─── OCR helpers ──────────────────────────────────────────────────────────

    /**
     * Called by ExtractOcrTextJob to persist extracted text and update status.
     */
    public function updateOcrResult(string $documentId, string $text, bool $success = true): void
    {
        $document = $this->documentRepo->findById($documentId);

        if (!$document) {
            Log::warning("updateOcrResult: Document {$documentId} not found.");
            return;
        }

        $this->documentRepo->update($document, [
            'ocr_extracted_text' => $text,
            'ocr_status'         => $success ? Document::OCR_DONE : Document::OCR_FAILED,
            'ocr_error'          => $success ? null : $text,
        ]);
    }

    /**
     * Mark document OCR as processing (called when job starts).
     */
    public function markOcrProcessing(string $documentId): void
    {
        $document = $this->documentRepo->findById($documentId);
        if ($document) {
            $this->documentRepo->update($document, ['ocr_status' => Document::OCR_PROCESSING]);
        }
    }

    /**
     * Backward compat — used by ExtractOcrTextJob.
     */
    public function updateOcrText(string $documentId, string $text): void
    {
        $this->updateOcrResult($documentId, $text, true);
    }

    /**
     * Return OCR status summary for a document.
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function getOcrStatus(User $actor, string $id): array
    {
        $document = $this->findForUser($actor, $id);

        return [
            'document_id'        => (string) $document->_id,
            'ocr_status'         => $document->ocr_status,
            'ocr_extracted_text' => $document->ocr_status === Document::OCR_DONE
                ? $document->ocr_extracted_text
                : null,
            'ocr_error'          => $document->ocr_error,
            'mime_type'          => $document->mime_type,
        ];
    }

    // ─── Generic ──────────────────────────────────────────────────────────────

    /** @deprecated Use findForUser() in controllers */
    public function findById(string $id): ?Document
    {
        return $this->documentRepo->findById($id);
    }

    public function listForBusiness(string $businessId): \Illuminate\Support\Collection
    {
        return $this->documentRepo->findByBusiness($businessId);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function requireBusinessId(User $user): string
    {
        if (!$user->business_id) {
            throw new \RuntimeException('Your account has no linked business.');
        }
        return (string) $user->business_id;
    }

    private function assertBusinessOwnership(User $actor, string $businessId): void
    {
        if ($this->requireBusinessId($actor) !== $businessId && $actor->role !== 'admin') {
            throw new AuthorizationException('You do not have access to this business.');
        }
    }
}
