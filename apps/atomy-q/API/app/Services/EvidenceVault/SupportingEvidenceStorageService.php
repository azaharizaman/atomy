<?php

declare(strict_types=1);

namespace App\Services\EvidenceVault;

use App\Models\Rfq;
use App\Models\SupportingEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SupportingEvidenceStorageService
{
    /**
     * @param array{vendor_id?: string|null, quote_submission_id?: string|null, award_id?: string|null} $relations
     */
    public function store(
        string $tenantId,
        Rfq $rfq,
        User $actor,
        UploadedFile $file,
        string $reason,
        array $relations = [],
    ): SupportingEvidence {
        $originalName = $this->sanitizeOriginalName($file->getClientOriginalName());
        $path = sprintf(
            'supporting-evidence/%s/%s/%s-%s',
            $tenantId,
            (string) $rfq->id,
            (string) Str::ulid(),
            $originalName,
        );

        $disk = Storage::disk('local');
        $storedPath = $disk->putFileAs('', $file, $path);
        if ($storedPath === false) {
            throw new RuntimeException('Supporting evidence file could not be stored.');
        }

        try {
            $contents = $disk->get($storedPath);
            if (! is_string($contents)) {
                throw new RuntimeException('Supporting evidence file could not be read after storage.');
            }

            $checksum = hash('sha256', $contents);

            return SupportingEvidence::query()->create([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfq->id,
                'vendor_id' => $relations['vendor_id'] ?? null,
                'quote_submission_id' => $relations['quote_submission_id'] ?? null,
                'award_id' => $relations['award_id'] ?? null,
                'reason' => $reason,
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'storage_path' => $storedPath,
                'checksum' => $checksum,
                'uploaded_by' => $actor->id,
                'uploaded_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            try {
                $disk->delete($storedPath);
            } catch (Throwable) {
                // Preserve the original storage/database failure for the caller.
            }

            throw $throwable;
        }
    }

    private function sanitizeOriginalName(string $name): string
    {
        $sanitized = preg_replace('/[\/\\\\\x00-\x1F\x7F]+/', '-', $name) ?? '';
        $sanitized = trim($sanitized, " \t\n\r\0\x0B.");

        if ($sanitized === '') {
            return 'supporting-evidence';
        }

        return Str::limit($sanitized, 180, '');
    }
}
