<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Document extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'documents';

    /**
     * OCR status constants
     */
    const OCR_PENDING    = 'pending';
    const OCR_PROCESSING = 'processing';
    const OCR_DONE       = 'done';
    const OCR_FAILED     = 'failed';
    const OCR_SKIPPED    = 'skipped'; // non-image/pdf types

    /**
     * Document categories
     */
    const CATEGORIES = [
        'license', 'certificate', 'permit', 'contract',
        'tax', 'insurance', 'registration', 'other',
    ];

    protected $fillable = [
        'business_id',
        'title',
        'category',
        'cloudinary_url',
        'cloudinary_public_id',
        'file_size',           // bytes (int)
        'mime_type',           // e.g. application/pdf, image/jpeg
        'original_filename',
        'ocr_status',          // pending | processing | done | failed | skipped
        'ocr_extracted_text',
        'ocr_error',           // last OCR error message if failed
        'uploaded_by',         // user _id
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'file_size'   => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function renewal()
    {
        return $this->hasOne(Renewal::class, 'document_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isOcrPending(): bool
    {
        return $this->ocr_status === self::OCR_PENDING;
    }

    public function isOcrDone(): bool
    {
        return $this->ocr_status === self::OCR_DONE;
    }

    /**
     * Determine if this file type supports OCR.
     */
    public static function supportsOcr(string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg', 'image/jpg', 'image/png',
            'image/tiff', 'image/webp',
            'application/pdf',
        ]);
    }
}
