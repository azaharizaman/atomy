<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Disposal certification repository interface for persistence operations.
 *
 * Manages disposal certification records for regulatory compliance.
 * Certifications provide audit evidence that documents were properly
 * disposed of according to retention policies.
 *
 * All operations must be tenant-scoped.
 */
interface DisposalCertificationRepositoryInterface
{
    /**
     * Find a disposal certification by its unique identifier.
     *
     * @param string $id Certification ULID
     * @return DisposalCertificationInterface|null Null if not found
     */
    public function findById(string $id): ?DisposalCertificationInterface;

    /**
     * Find a disposal certification by original document ID.
     *
     * @param string $documentId Original document ULID
     * @return DisposalCertificationInterface|null Null if not found
     */
    public function findByDocumentId(string $documentId): ?DisposalCertificationInterface;

    /**
     * Find certifications within a date range.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array<DisposalCertificationInterface>
     */
    public function findByDateRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array;

    /**
     * Find certifications by disposal method.
     *
     * @param string $method Disposal method (e.g., SECURE_DELETE, ANONYMIZE)
     * @return array<DisposalCertificationInterface>
     */
    public function findByDisposalMethod(string $method): array;

    /**
     * Find certifications by the user who performed disposal.
     *
     * @param string $userId User ULID
     * @return array<DisposalCertificationInterface>
     */
    public function findByDisposedBy(string $userId): array;

    /**
     * Find certifications by document type.
     *
     * @param string $documentType Document type
     * @return array<DisposalCertificationInterface>
     */
    public function findByDocumentType(string $documentType): array;

    /**
     * Find certifications by regulatory basis.
     *
     * @param string $regulatoryBasis Regulatory reference (e.g., "SOX Section 802")
     * @return array<DisposalCertificationInterface>
     */
    public function findByRegulatoryBasis(string $regulatoryBasis): array;

    /**
     * Save a disposal certification (create or update).
     *
     * @param DisposalCertificationInterface $certification Certification entity
     */
    public function save(DisposalCertificationInterface $certification): void;

    /**
     * Create a new disposal certification record.
     *
     * @param array<string, mixed> $attributes Key-value data for the new certification
     * @return DisposalCertificationInterface
     */
    public function create(array $attributes): DisposalCertificationInterface;

    /**
     * Count certifications in a date range.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     */
    public function countByDateRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): int;

    /**
     * Get disposal statistics for audit reporting.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array{
     *     total_disposed: int,
     *     by_method: array<string, int>,
     *     by_type: array<string, int>,
     *     compliant_count: int,
     *     with_witness_count: int
     * }
     */
    public function getStatistics(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array;

    /**
     * Find certifications where disposal was not timely.
     *
     * Returns certifications where disposal occurred more than
     * specified days after retention expiration.
     *
     * @param int $gracePeriodDays Number of days considered timely (e.g., 30)
     * @return array<DisposalCertificationInterface>
     */
    public function findLateDisposals(int $gracePeriodDays = 30): array;

    /**
     * Check if a document has been disposed (has certification).
     *
     * @param string $documentId Document ULID
     * @return bool True if disposal certification exists
     */
    public function hasBeenDisposed(string $documentId): bool;
}
