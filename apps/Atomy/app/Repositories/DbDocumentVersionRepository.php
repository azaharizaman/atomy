<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentVersion;
use Nexus\Document\Contracts\DocumentVersionInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;

/**
 * Database-backed document version repository.
 *
 * Implements DocumentVersionRepositoryInterface using Eloquent.
 */
final readonly class DbDocumentVersionRepository implements DocumentVersionRepositoryInterface
{
    public function findById(string $id): ?DocumentVersionInterface
    {
        return DocumentVersion::find($id);
    }

    public function findByDocumentId(string $documentId): array
    {
        return DocumentVersion::where('document_id', $documentId)
            ->orderBy('version_number', 'desc')
            ->get()
            ->all();
    }

    public function findByVersion(string $documentId, int $versionNumber): ?DocumentVersionInterface
    {
        return DocumentVersion::where('document_id', $documentId)
            ->where('version_number', $versionNumber)
            ->first();
    }

    public function save(DocumentVersionInterface $version): void
    {
        if ($version instanceof DocumentVersion) {
            $version->save();
        }
    }

    public function delete(string $id): void
    {
        DocumentVersion::where('id', $id)->delete();
    }

    public function getLatestVersion(string $documentId): ?DocumentVersionInterface
    {
        return DocumentVersion::where('document_id', $documentId)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    public function countVersions(string $documentId): int
    {
        return DocumentVersion::where('document_id', $documentId)->count();
    }

    public function deleteOlderThan(string $documentId, int $keepFromVersion): int
    {
        return DocumentVersion::where('document_id', $documentId)
            ->where('version_number', '<', $keepFromVersion)
            ->delete();
    }

    public function create(array $attributes): DocumentVersionInterface
    {
        $version = new DocumentVersion($attributes);
        $version->save();
        return $version;
    }
}
