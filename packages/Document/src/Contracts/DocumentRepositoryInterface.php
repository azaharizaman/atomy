<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\DocumentType;

/**
 * Document repository interface for persistence operations.
 *
 * Defines CRUD operations and query methods for document entities.
 * All operations must be tenant-scoped.
 */
interface DocumentRepositoryInterface
{
    /**
     * Find a document by its unique identifier.
     *
     * @param string $id Document ULID
     * @return DocumentInterface|null Null if not found or not accessible
     */
    public function findById(string $id): ?DocumentInterface;

    /**
     * Find all documents owned by a specific user.
     *
     * @param string $ownerId Owner ULID
     * @return array<DocumentInterface>
     */
    public function findByOwner(string $ownerId): array;

    /**
     * Find all documents of a specific type.
     *
     * @param DocumentType $type Document type enum
     * @return array<DocumentInterface>
     */
    public function findByType(DocumentType $type): array;

    /**
     * Find documents by tags (JSON metadata query).
     *
     * @param array<string> $tags Array of tag strings
     * @return array<DocumentInterface>
     */
    public function findByTags(array $tags): array;

    /**
     * Save a document (create or update).
     *
     * @param DocumentInterface $document Document entity
     */
    public function save(DocumentInterface $document): void;

    /**
     * Soft delete a document.
     *
     * @param string $id Document ULID
     */
    public function delete(string $id): void;

    /**
     * Check if a document exists.
     *
     * @param string $id Document ULID
     */
    public function exists(string $id): bool;

    /**
     * Get the complete version history for a document.
     *
     * @param string $documentId Document ULID
     * @return array<DocumentVersionInterface> Versions ordered by version DESC
     */
    public function getVersionHistory(string $documentId): array;

    /**
     * Find documents within a date range.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array<DocumentInterface>
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Count total documents for a tenant.
     */
    public function count(): int;

    /**
     * Get all soft-deleted documents (for retention/purge operations).
     *
     * @return array<DocumentInterface>
     */
    public function getDeleted(): array;

    /**
     * Create a new document record.
     *
     * @param array $attributes Key-value data for the new document
     * @return DocumentInterface
     */
    public function create(array $attributes): DocumentInterface;

    /**
     * Permanently delete a document (hard delete).
     *
     * WARNING: This operation cannot be undone. Use only after
     * retention period has expired and legal hold is verified cleared.
     *
     * @param string $id Document ULID
     * @throws \Nexus\Document\Exceptions\DocumentNotFoundException If document not found
     */
    public function forceDelete(string $id): void;

    /**
     * Find documents by their current state.
     *
     * @param \Nexus\Document\ValueObjects\DocumentState $state Document state
     * @return array<DocumentInterface>
     */
    public function findByState(\Nexus\Document\ValueObjects\DocumentState $state): array;

    /**
     * Find documents eligible for disposal.
     *
     * Returns documents that:
     * - Are past their retention period (based on creation date and retention days)
     * - Have no active legal hold
     * - Are in DELETED or ARCHIVED state
     *
     * @param \DateTimeInterface $retentionCutoff Documents created before this date are eligible
     * @param DocumentType|null $type Optional filter by document type
     * @return array<DocumentInterface>
     */
    public function findEligibleForDisposal(
        \DateTimeInterface $retentionCutoff,
        ?DocumentType $type = null
    ): array;

    /**
     * Find documents by custom metadata field.
     *
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return array<DocumentInterface>
     */
    public function findByMetadata(string $key, mixed $value): array;
}
