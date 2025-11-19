<?php

declare(strict_types=1);

namespace App\Repositories\Import;

use App\Models\Import;
use Nexus\Import\Contracts\ImportRepositoryInterface;
use Nexus\Import\ValueObjects\ImportRecord;
use Nexus\Import\ValueObjects\ImportMetadata;
use Nexus\Import\ValueObjects\ImportMode;
use Nexus\Import\ValueObjects\ImportStatus;

/**
 * Eloquent implementation of ImportRepository
 */
final class DbImportRepository implements ImportRepositoryInterface
{
    public function save(ImportRecord $record): void
    {
        Import::create([
            'id' => $record->id,
            'handler_type' => $record->handlerType,
            'mode' => $record->mode->value,
            'status' => $record->status->value,
            'original_file_name' => $record->metadata->originalFileName,
            'file_size' => $record->metadata->fileSize,
            'mime_type' => $record->metadata->mimeType,
            'uploaded_at' => $record->metadata->uploadedAt,
            'uploaded_by' => $record->metadata->uploadedBy,
            'tenant_id' => $record->metadata->tenantId,
            'started_at' => $record->startedAt,
            'completed_at' => $record->completedAt,
        ]);
    }

    public function update(ImportRecord $record): void
    {
        Import::where('id', $record->id)->update([
            'status' => $record->status->value,
            'started_at' => $record->startedAt,
            'completed_at' => $record->completedAt,
        ]);
    }

    public function findById(string $id): ?ImportRecord
    {
        $import = Import::find($id);

        if (!$import) {
            return null;
        }

        return $this->toRecord($import);
    }

    public function findByTenant(string $tenantId, int $limit = 50): array
    {
        $imports = Import::where('tenant_id', $tenantId)
            ->orderBy('uploaded_at', 'desc')
            ->limit($limit)
            ->get();

        return $imports->map(fn($import) => $this->toRecord($import))->all();
    }

    public function findPending(int $limit = 10): array
    {
        $imports = Import::where('status', ImportStatus::PENDING->value)
            ->orderBy('uploaded_at', 'asc')
            ->limit($limit)
            ->get();

        return $imports->map(fn($import) => $this->toRecord($import))->all();
    }

    public function delete(string $id): void
    {
        Import::destroy($id);
    }

    private function toRecord(Import $import): ImportRecord
    {
        return new ImportRecord(
            id: $import->id,
            handlerType: $import->handler_type,
            mode: ImportMode::from($import->mode),
            status: ImportStatus::from($import->status),
            metadata: new ImportMetadata(
                originalFileName: $import->original_file_name,
                fileSize: $import->file_size,
                mimeType: $import->mime_type,
                uploadedAt: $import->uploaded_at,
                uploadedBy: $import->uploaded_by,
                tenantId: $import->tenant_id,
            ),
            startedAt: $import->started_at,
            completedAt: $import->completed_at
        );
    }
}
