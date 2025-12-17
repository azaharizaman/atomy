<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Disposal certification entity interface.
 *
 * Represents a compliance record proving that a document was
 * properly disposed of according to retention policies.
 *
 * Disposal certifications are critical for regulatory compliance
 * (SOX, GDPR, HIPAA) and provide audit evidence that documents
 * were not destroyed prematurely or retained beyond required periods.
 */
interface DisposalCertificationInterface
{
    /**
     * Get the unique disposal certification identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the tenant identifier for multi-tenancy isolation.
     */
    public function getTenantId(): string;

    /**
     * Get the original document identifier that was disposed.
     */
    public function getDocumentId(): string;

    /**
     * Get the original document type.
     */
    public function getDocumentType(): string;

    /**
     * Get the original document name/title.
     */
    public function getDocumentName(): string;

    /**
     * Get the disposal method used.
     *
     * Common methods: SECURE_DELETE, ARCHIVE_TO_COLD_STORAGE,
     * ANONYMIZE, PHYSICAL_DESTRUCTION
     */
    public function getDisposalMethod(): string;

    /**
     * Get the user who performed the disposal.
     */
    public function getDisposedBy(): string;

    /**
     * Get the timestamp when disposal occurred.
     */
    public function getDisposedAt(): \DateTimeInterface;

    /**
     * Get the user who approved the disposal (if applicable).
     */
    public function getApprovedBy(): ?string;

    /**
     * Get the timestamp when disposal was approved (if applicable).
     */
    public function getApprovedAt(): ?\DateTimeInterface;

    /**
     * Get the reason for disposal.
     */
    public function getDisposalReason(): string;

    /**
     * Get the original document creation date.
     *
     * Used to verify retention period compliance.
     */
    public function getDocumentCreatedAt(): \DateTimeInterface;

    /**
     * Get the retention period that applied to the document (in days).
     */
    public function getRetentionPeriodDays(): int;

    /**
     * Get the date when retention period expired.
     */
    public function getRetentionExpiredAt(): \DateTimeInterface;

    /**
     * Check if legal hold was verified before disposal.
     */
    public function isLegalHoldVerified(): bool;

    /**
     * Get the verification hash of the original document.
     *
     * Provides cryptographic proof of document identity.
     */
    public function getDocumentChecksum(): string;

    /**
     * Get the regulatory basis for retention.
     *
     * E.g., "SOX Section 802", "GDPR Article 17", "IRS Revenue Procedure"
     */
    public function getRegulatoryBasis(): ?string;

    /**
     * Get the witness user ID (for dual-control disposal).
     */
    public function getWitnessedBy(): ?string;

    /**
     * Get additional certification metadata.
     *
     * May include original file metadata, audit trail references,
     * chain of custody information.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get chain of custody records.
     *
     * Documents the handling of the record from creation to disposal.
     *
     * @return array<array{action: string, by: string, at: string, notes: string|null}>
     */
    public function getChainOfCustody(): array;

    /**
     * Generate a compliance report summary.
     *
     * @return array{
     *     certification_id: string,
     *     document_id: string,
     *     document_name: string,
     *     document_type: string,
     *     retention_days: int,
     *     retention_compliant: bool,
     *     legal_hold_clear: bool,
     *     disposed_by: string,
     *     disposed_at: string,
     *     disposal_method: string,
     *     regulatory_basis: string|null,
     *     has_witness: bool
     * }
     */
    public function toComplianceReport(): array;

    /**
     * Convert the certification to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
