<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\DocumentType;

/**
 * Document search interface for metadata-based queries.
 *
 * Provides search capabilities based on document metadata.
 * All searches are tenant-scoped and permission-filtered.
 * Future implementations may extend to full-text search.
 */
interface DocumentSearchInterface
{
    /**
     * Find documents by tags.
     *
     * @param array<string> $tags Array of tag strings
     * @param string $userId User ULID for permission filtering
     * @return array<DocumentInterface>
     */
    public function findByTags(array $tags, string $userId): array;

    /**
     * Find documents by type with optional filters.
     *
     * @param DocumentType $type Document type enum
     * @param array<string, mixed> $filters Optional filters (dateFrom, dateTo, ownerId)
     * @param string $userId User ULID for permission filtering
     * @return array<DocumentInterface>
     */
    public function findByType(DocumentType $type, array $filters, string $userId): array;

    /**
     * Find documents by metadata criteria (JSON queries).
     *
     * @param array<string, mixed> $criteria Key-value pairs for metadata matching
     * @param string $userId User ULID for permission filtering
     * @return array<DocumentInterface>
     */
    public function findByMetadata(array $criteria, string $userId): array;

    /**
     * Find documents by owner with optional filters.
     *
     * @param string $ownerId Owner user ULID
     * @param array<string, mixed> $filters Optional filters (type, state, dateFrom, dateTo)
     * @param string $userId User ULID for permission filtering
     * @return array<DocumentInterface>
     */
    public function findByOwner(string $ownerId, array $filters, string $userId): array;

    /**
     * Find documents within a date range.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @param string $userId User ULID for permission filtering
     * @return array<DocumentInterface>
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, string $userId): array;
}
