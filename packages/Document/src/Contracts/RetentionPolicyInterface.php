<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Retention policy interface for compliance-aware document retention.
 *
 * Integrates with Nexus\Compliance package when available.
 * Defines rules for document retention periods and purging.
 *
 * This interface is typically implemented by consuming applications
 * using their chosen storage mechanism (database, configuration, etc.).
 */
interface RetentionPolicyInterface
{
    /**
     * Get the retention period in days for a specific document type.
     *
     * @param string $documentType Document type string
     * @return int Number of days to retain the document
     */
    public function getRetentionDays(string $documentType): int;

    /**
     * Get the retention period in years for a specific document type.
     *
     * Convenience method for regulatory compliance (SOX 7 years, etc.).
     *
     * @param string $documentType Document type string
     * @return int Number of years to retain the document
     */
    public function getRetentionYears(string $documentType): int;

    /**
     * Check if a document has expired its retention period.
     *
     * @param \DateTimeInterface $createdAt Document creation date
     * @param string $documentType Document type string
     */
    public function isExpired(\DateTimeInterface $createdAt, string $documentType): bool;

    /**
     * Calculate the expiration date for a document.
     *
     * @param \DateTimeInterface $createdAt Document creation date
     * @param string $documentType Document type string
     * @return \DateTimeImmutable The date when retention period expires
     */
    public function getExpirationDate(\DateTimeInterface $createdAt, string $documentType): \DateTimeImmutable;

    /**
     * Check if a document can be permanently purged.
     *
     * Considers legal holds, active litigation, and compliance requirements.
     *
     * @param string $documentId Document ULID
     */
    public function canPurge(string $documentId): bool;

    /**
     * Check if a legal hold is active on a document.
     *
     * @param string $documentId Document ULID
     */
    public function hasLegalHold(string $documentId): bool;

    /**
     * Apply a legal hold to a document (prevents deletion).
     *
     * @param string $documentId Document ULID
     * @param string $reason Reason for legal hold
     * @param string $appliedBy User ULID who applied the hold
     * @param string|null $matterReference Optional legal matter/case reference
     * @param \DateTimeInterface|null $expiresAt Optional expiration date for the hold
     * @return LegalHoldInterface The created legal hold record
     */
    public function applyLegalHold(
        string $documentId,
        string $reason,
        string $appliedBy,
        ?string $matterReference = null,
        ?\DateTimeInterface $expiresAt = null
    ): LegalHoldInterface;

    /**
     * Release a legal hold from a document.
     *
     * @param string $documentId Document ULID
     * @param string $releasedBy User ULID who released the hold
     * @param string|null $releaseReason Reason for releasing the hold
     * @return LegalHoldInterface The updated legal hold record
     */
    public function releaseLegalHold(
        string $documentId,
        string $releasedBy,
        ?string $releaseReason = null
    ): LegalHoldInterface;

    /**
     * Get all active legal holds for a document.
     *
     * @param string $documentId Document ULID
     * @return array<LegalHoldInterface>
     */
    public function getActiveLegalHolds(string $documentId): array;

    /**
     * Get the regulatory basis for retention of a document type.
     *
     * @param string $documentType Document type string
     * @return string|null Regulatory reference (e.g., "SOX Section 802") or null
     */
    public function getRegulatoryBasis(string $documentType): ?string;

    /**
     * Get the default disposal method for a document type.
     *
     * @param string $documentType Document type string
     * @return string Disposal method (e.g., "SECURE_DELETE", "ARCHIVE")
     */
    public function getDisposalMethod(string $documentType): string;
}
