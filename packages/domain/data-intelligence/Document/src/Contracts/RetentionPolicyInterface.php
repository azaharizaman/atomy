<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Retention policy interface for compliance-aware document retention.
 *
 * Integrates with Nexus\Compliance package when available.
 * Defines rules for document retention periods and purging.
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
     * Check if a document has expired its retention period.
     *
     * @param \DateTimeInterface $createdAt Document creation date
     * @param string $documentType Document type string
     */
    public function isExpired(\DateTimeInterface $createdAt, string $documentType): bool;

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
     */
    public function applyLegalHold(string $documentId, string $reason, string $appliedBy): void;

    /**
     * Release a legal hold from a document.
     *
     * @param string $documentId Document ULID
     * @param string $releasedBy User ULID who released the hold
     */
    public function releaseLegalHold(string $documentId, string $releasedBy): void;
}
