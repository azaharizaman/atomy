<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Legal hold repository interface for persistence operations.
 *
 * Manages legal hold records for documents. Legal holds prevent
 * document disposal during litigation or regulatory investigation.
 *
 * All operations must be tenant-scoped.
 */
interface LegalHoldRepositoryInterface
{
    /**
     * Find a legal hold by its unique identifier.
     *
     * @param string $id Legal hold ULID
     * @return LegalHoldInterface|null Null if not found
     */
    public function findById(string $id): ?LegalHoldInterface;

    /**
     * Find all legal holds for a specific document.
     *
     * @param string $documentId Document ULID
     * @return array<LegalHoldInterface>
     */
    public function findByDocumentId(string $documentId): array;

    /**
     * Find active legal holds for a document.
     *
     * @param string $documentId Document ULID
     * @return array<LegalHoldInterface> Active (non-released) holds only
     */
    public function findActiveByDocumentId(string $documentId): array;

    /**
     * Check if a document has any active legal holds.
     *
     * @param string $documentId Document ULID
     * @return bool True if document has at least one active hold
     */
    public function hasActiveHold(string $documentId): bool;

    /**
     * Find all active legal holds for the tenant.
     *
     * @return array<LegalHoldInterface>
     */
    public function findAllActive(): array;

    /**
     * Find legal holds by matter reference.
     *
     * @param string $matterReference Legal matter/case reference
     * @return array<LegalHoldInterface>
     */
    public function findByMatterReference(string $matterReference): array;

    /**
     * Find legal holds applied by a specific user.
     *
     * @param string $userId User ULID who applied the holds
     * @return array<LegalHoldInterface>
     */
    public function findByAppliedBy(string $userId): array;

    /**
     * Save a legal hold (create or update).
     *
     * @param LegalHoldInterface $legalHold Legal hold entity
     */
    public function save(LegalHoldInterface $legalHold): void;

    /**
     * Create a new legal hold record.
     *
     * @param array<string, mixed> $attributes Key-value data for the new hold
     * @return LegalHoldInterface
     */
    public function create(array $attributes): LegalHoldInterface;

    /**
     * Find legal holds expiring within a date range.
     *
     * Useful for proactive notifications about expiring holds.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array<LegalHoldInterface>
     */
    public function findExpiringBetween(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array;

    /**
     * Count total active legal holds for a tenant.
     */
    public function countActive(): int;

    /**
     * Get documents with active legal holds.
     *
     * Returns unique document IDs that have at least one active hold.
     *
     * @return array<string> Document ULIDs
     */
    public function getDocumentIdsWithActiveHolds(): array;
}
