<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentRelationship;
use Nexus\Document\Contracts\DocumentRelationshipInterface;
use Nexus\Document\Contracts\DocumentRelationshipRepositoryInterface;
use Nexus\Document\ValueObjects\RelationshipType;

/**
 * Database-backed document relationship repository.
 *
 * Implements DocumentRelationshipRepositoryInterface using Eloquent.
 */
final readonly class DbDocumentRelationshipRepository implements DocumentRelationshipRepositoryInterface
{
    public function findById(string $id): ?DocumentRelationshipInterface
    {
        return DocumentRelationship::find($id);
    }

    public function create(
        string $sourceDocumentId,
        string $targetDocumentId,
        RelationshipType $type,
        string $createdBy
    ): DocumentRelationshipInterface {
        $relationship = new DocumentRelationship([
            'id' => $this->generateUlid(),
            'source_document_id' => $sourceDocumentId,
            'target_document_id' => $targetDocumentId,
            'relationship_type' => $type,
            'created_by' => $createdBy,
        ]);

        $relationship->save();

        return $relationship;
    }

    public function delete(string $id): void
    {
        DocumentRelationship::where('id', $id)->delete();
    }

    public function findBySourceDocument(string $sourceDocumentId, ?RelationshipType $type = null): array
    {
        $query = DocumentRelationship::where('source_document_id', $sourceDocumentId);

        if ($type !== null) {
            $query->where('relationship_type', $type->value);
        }

        return $query->with('targetDocument')->get()->all();
    }

    public function findByTargetDocument(string $targetDocumentId, ?RelationshipType $type = null): array
    {
        $query = DocumentRelationship::where('target_document_id', $targetDocumentId);

        if ($type !== null) {
            $query->where('relationship_type', $type->value);
        }

        return $query->with('sourceDocument')->get()->all();
    }

    public function findByType(RelationshipType $type): array
    {
        return DocumentRelationship::where('relationship_type', $type->value)
            ->with(['sourceDocument', 'targetDocument'])
            ->get()
            ->all();
    }

    public function exists(
        string $sourceDocumentId,
        string $targetDocumentId,
        RelationshipType $type
    ): bool {
        return DocumentRelationship::where('source_document_id', $sourceDocumentId)
            ->where('target_document_id', $targetDocumentId)
            ->where('relationship_type', $type->value)
            ->exists();
    }

    public function deleteByDocument(string $documentId): int
    {
        return DocumentRelationship::where('source_document_id', $documentId)
            ->orWhere('target_document_id', $documentId)
            ->delete();
    }

    private function generateUlid(): string
    {
        return strtoupper(sprintf('%026s', bin2hex(random_bytes(13))));
    }
}
