<?php

namespace App\Jobs;

use App\Services\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ExtractOcrTextJob
 *
 * Dispatched after a document is uploaded to Cloudinary.
 * Downloads the file, runs Tesseract OCR, saves the extracted text.
 *
 * Queue:   'default' (Redis when QUEUE_CONNECTION=redis, else sync)
 * Retries: 3
 * Timeout: 120s
 */
class ExtractOcrTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    /** Backoff: 10s, 30s, 90s between retries */
    public array $backoff = [10, 30, 90];

    public function __construct(private readonly string $documentId) {}

    public function handle(DocumentService $documentService): void
    {
        // Mark as processing so the frontend can show a spinner
        $documentService->markOcrProcessing($this->documentId);

        $document = \App\Models\Document::find($this->documentId);

        if (!$document || !$document->cloudinary_url) {
            Log::warning("ExtractOcrTextJob: document {$this->documentId} not found or has no Cloudinary URL.");
            $documentService->updateOcrResult($this->documentId, 'Document not found.', false);
            return;
        }

        $tempInput  = null;
        $tempOutput = null;

        try {
            // ── 1. Read file (from local disk or Cloudinary URL) ─────────────
            $tempInput = tempnam(sys_get_temp_dir(), 'complysmart_ocr_in_');

            // Add correct extension so Tesseract knows the type
            $ext = $this->extensionForMime($document->mime_type ?? '');
            if ($ext) {
                rename($tempInput, $tempInput . $ext);
                $tempInput .= $ext;
            }

            $fileContent = false;
            if (str_starts_with($document->cloudinary_public_id ?? '', 'local:')) {
                $localPath = substr($document->cloudinary_public_id, 6);
                $fullPath  = storage_path("app/public/{$localPath}");
                if (file_exists($fullPath)) {
                    $fileContent = file_get_contents($fullPath);
                }
            }

            if ($fileContent === false) {
                $fileContent = @file_get_contents($document->cloudinary_url);
            }

            if ($fileContent === false) {
                throw new \RuntimeException("Failed to read document file: {$document->cloudinary_url}");
            }
            file_put_contents($tempInput, $fileContent);

            // ── 2. Run Tesseract ──────────────────────────────────────────────
            $tesseractPath = config('services.tesseract.path', 'tesseract');
            $tempOutput    = tempnam(sys_get_temp_dir(), 'complysmart_ocr_out_');
            // Remove the extension tempnam adds — Tesseract will append .txt itself
            @unlink($tempOutput);

            // Windows-compatible: redirect stderr to NUL instead of /dev/null
            $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
            $cmd = "\"{$tesseractPath}\" \"{$tempInput}\" \"{$tempOutput}\" --psm 6 2>\"{$nullDevice}\"";

            exec($cmd, $execOutput, $exitCode);

            $outputTxt = $tempOutput . '.txt';
            $text      = '';

            if ($exitCode === 0 && file_exists($outputTxt)) {
                $text = trim(file_get_contents($outputTxt));
                @unlink($outputTxt);
            } else {
                throw new \RuntimeException(
                    "Tesseract exited with code {$exitCode}. CMD: {$cmd}"
                );
            }

            // ── 3. Persist result ─────────────────────────────────────────────
            $documentService->updateOcrResult($this->documentId, $text, true);
            Log::info("OCR done for document {$this->documentId}. Extracted " . strlen($text) . " chars.");

        } catch (\Throwable $e) {
            Log::error("ExtractOcrTextJob failed [{$this->documentId}]: " . $e->getMessage());
            $documentService->updateOcrResult($this->documentId, $e->getMessage(), false);

            // Do NOT re-throw — OCR failure must never block or fail the document upload.
            // The document is already saved in MongoDB. OCR status is marked 'failed'.
            // A future manual retry or re-upload can re-trigger OCR.
        } finally {
            // Always clean up temp files
            if ($tempInput && file_exists($tempInput)) @unlink($tempInput);
        }
    }

    /**
     * Map MIME type to a file extension for Tesseract compatibility.
     */
    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => '.jpg',
            'image/png'               => '.png',
            'image/tiff'              => '.tiff',
            'image/webp'              => '.webp',
            'application/pdf'         => '.pdf',
            default                   => '',
        };
    }
}
