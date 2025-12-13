<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Contract for document retention management.
 *
 * Implements configurable retention policies for regulatory compliance:
 * - SOX Data: 7 Years
 * - Vendor Contracts: 3 Years
 * - RFQ Data: 2 Years
 * - General AP Data: 5 Years
 */
interface DocumentRetentionServiceInterface
{
    /**
     * Get retention policy for document type.
     *
     * @param string $documentType Document category
     * @return array{years: int, legal_hold: bool, disposal_method: string, regulatory_basis: string}
     */
    public function getRetentionPolicy(string $documentType): array;

    /**
     * Check if document is within retention period.
     *
     * @param string $documentType Document category
     * @param \DateTimeImmutable $createdAt Document creation date
     * @return bool True if document should be retained
     */
    public function isWithinRetentionPeriod(string $documentType, \DateTimeImmutable $createdAt): bool;

    /**
     * Get documents eligible for disposal.
     *
     * @param string $documentType Document category
     * @param \DateTimeImmutable $asOfDate Check date (default: now)
     * @return array Documents eligible for disposal
     */
    public function getDocumentsForDisposal(string $documentType, ?\DateTimeImmutable $asOfDate = null): array;

    /**
     * Apply legal hold to document.
     *
     * @param string $documentId Document identifier
     * @param string $holdReason Reason for legal hold
     * @param string $holdBy User applying hold
     * @return array Hold confirmation details
     */
    public function applyLegalHold(string $documentId, string $holdReason, string $holdBy): array;

    /**
     * Release legal hold from document.
     *
     * @param string $documentId Document identifier
     * @param string $releaseReason Reason for releasing hold
     * @param string $releasedBy User releasing hold
     * @return array Release confirmation details
     */
    public function releaseLegalHold(string $documentId, string $releaseReason, string $releasedBy): array;

    /**
     * Get retention report for audit.
     *
     * @param string $tenantId Tenant context
     * @param \DateTimeImmutable $periodStart Report period start
     * @param \DateTimeImmutable $periodEnd Report period end
     * @return array Retention compliance report
     */
    public function getRetentionReport(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array;

    /**
     * Archive document for long-term storage.
     *
     * @param string $documentId Document identifier
     * @param string $archiveReason Reason for archival
     * @param string $archivedBy User performing archive
     * @return array Archive confirmation details
     */
    public function archiveDocument(string $documentId, string $archiveReason, string $archivedBy): array;

    /**
     * Dispose document after retention period.
     *
     * @param string $documentId Document identifier
     * @param string $disposalMethod Method of disposal (SECURE_DELETE, ARCHIVE, ANONYMIZE)
     * @param string $disposedBy User performing disposal
     * @param string $certificationId Optional certification reference
     * @return array Disposal certification details
     */
    public function disposeDocument(
        string $documentId,
        string $disposalMethod,
        string $disposedBy,
        ?string $certificationId = null,
    ): array;
}
