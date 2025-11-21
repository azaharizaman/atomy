<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Document;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\ValueObjects\DocumentType;

/**
 * Database-backed document repository.
 *
 * Implements DocumentRepositoryInterface using Eloquent.
 */
final readonly class DbDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(string $id): ?DocumentInterface
    {
        return Document::find($id);
    }

    public function findByOwner(string $ownerId): array
    {
        return Document::where('owner_id', $ownerId)
            ->with('versions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByType(DocumentType $type): array
    {
        return Document::where('type', $type->value)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByTags(array $tags): array
    {
        return Document::whereJsonContains('metadata->tags', $tags)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function save(DocumentInterface $document): void
    {
        if ($document instanceof Document) {
            $document->save();
        }
    }

    public function delete(string $id): void
    {
        $document = Document::find($id);
        if ($document) {
            $document->delete(); // Soft delete
        }
    }

    public function exists(string $id): bool
    {
        return Document::where('id', $id)->exists();
    }

    public function getVersionHistory(string $documentId): array
    {
        $document = Document::with('versions')->find($documentId);
        if (!$document) {
            return [];
        }

        return $document->versions()
            ->orderBy('version_number', 'desc')
            ->get()
            ->all();
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return Document::whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function count(): int
    {
        return Document::count();
    }

    public function getDeleted(): array
    {
        return Document::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get()
            ->all();
    }
}
