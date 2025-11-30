<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\RelationshipType;

/**
 * Document relationship repository interface for relationship persistence.
 *
 * Manages storage and retrieval of document relationship links.
 */
interface DocumentRelationshipRepositoryInterface
{
    /**
     * Find a relationship by its unique identifier.
     *
     * @param string $id Relationship ULID
     * @return DocumentRelationshipInterface|null Null if not found
     */
    public function findById(string $id): ?DocumentRelationshipInterface;

    /**
     * Create a new relationship.
     *
     * @param string $sourceDocumentId Source document ULID
     * @param string $targetDocumentId Target document ULID
     * @param RelationshipType $type Relationship type
     * @param string $createdBy Creator user ULID
     * @return DocumentRelationshipInterface Created relationship
     */
    public function create(
        string $sourceDocumentId,
        string $targetDocumentId,
        RelationshipType $type,
        string $createdBy
    ): DocumentRelationshipInterface;

    /**
     * Delete a relationship.
     *
     * @param string $id Relationship ULID
     */
    public function delete(string $id): void;

    /**
     * Find all relationships where the document is the source.
     *
     * @param string $sourceDocumentId Source document ULID
     * @param RelationshipType|null $type Optional filter by type
     * @return array<DocumentRelationshipInterface>
     */
    public function findBySourceDocument(string $sourceDocumentId, ?RelationshipType $type = null): array;

    /**
     * Find all relationships where the document is the target.
     *
     * @param string $targetDocumentId Target document ULID
     * @param RelationshipType|null $type Optional filter by type
     * @return array<DocumentRelationshipInterface>
     */
    public function findByTargetDocument(string $targetDocumentId, ?RelationshipType $type = null): array;

    /**
     * Find all relationships of a specific type.
     *
     * @param RelationshipType $type Relationship type
     * @return array<DocumentRelationshipInterface>
     */
    public function findByType(RelationshipType $type): array;

    /**
     * Check if a relationship exists between two documents.
     *
     * @param string $sourceDocumentId Source document ULID
     * @param string $targetDocumentId Target document ULID
     * @param RelationshipType $type Relationship type
     */
    public function exists(
        string $sourceDocumentId,
        string $targetDocumentId,
        RelationshipType $type
    ): bool;

    /**
     * Delete all relationships for a document (when document is deleted).
     *
     * @param string $documentId Document ULID
     * @return int Number of relationships deleted
     */
    public function deleteByDocument(string $documentId): int;
}
