<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Document version repository interface for version history persistence.
 *
 * Manages storage and retrieval of document version records.
 */
interface DocumentVersionRepositoryInterface
{
    /**
     * Find a version by its unique identifier.
     *
     * @param string $id Version ULID
     * @return DocumentVersionInterface|null Null if not found
     */
    public function findById(string $id): ?DocumentVersionInterface;

    /**
     * Find all versions for a specific document.
     *
     * @param string $documentId Document ULID
     * @return array<DocumentVersionInterface> Ordered by version DESC
     */
    public function findByDocumentId(string $documentId): array;

    /**
     * Find a specific version by document ID and version number.
     *
     * @param string $documentId Document ULID
     * @param int $versionNumber Version number (1-based)
     * @return DocumentVersionInterface|null Null if not found
     */
    public function findByVersion(string $documentId, int $versionNumber): ?DocumentVersionInterface;

    /**
     * Save a version record (create or update).
     *
     * @param DocumentVersionInterface $version Version entity
     */
    public function save(DocumentVersionInterface $version): void;

    /**
     * Delete a version record.
     *
     * @param string $id Version ULID
     */
    public function delete(string $id): void;

    /**
     * Get the latest version for a document.
     *
     * @param string $documentId Document ULID
     * @return DocumentVersionInterface|null Null if no versions exist
     */
    public function getLatestVersion(string $documentId): ?DocumentVersionInterface;

    /**
     * Count total versions for a document.
     *
     * @param string $documentId Document ULID
     */
    public function countVersions(string $documentId): int;

    /**
     * Delete all versions older than a specific version number.
     *
     * @param string $documentId Document ULID
     * @param int $keepFromVersion Keep versions >= this number
     * @return int Number of versions deleted
     */
    public function deleteOlderThan(string $documentId, int $keepFromVersion): int;

    /**
     * Create a new document version record.
     *
     * @param array $data Key-value data for the new version (document_id, version_number, etc.)
     * @return DocumentVersionInterface
     */
    public function create(array $data): DocumentVersionInterface;
}
