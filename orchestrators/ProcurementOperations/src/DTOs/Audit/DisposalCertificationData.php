<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

use Nexus\ProcurementOperations\Enums\RetentionCategory;

/**
 * DTO representing a disposal certification record.
 */
final readonly class DisposalCertificationData
{
    /**
     * @param string $certificationId Disposal certification identifier
     * @param string $tenantId Tenant context
     * @param string $documentId Disposed document identifier
     * @param string $documentType Document type
     * @param RetentionCategory $retentionCategory Document retention category
     * @param \DateTimeImmutable $documentCreatedAt Original document creation date
     * @param \DateTimeImmutable $retentionExpiredAt When retention period expired
     * @param \DateTimeImmutable $disposedAt When document was disposed
     * @param string $disposedBy User who performed disposal
     * @param string $approvedBy User who approved disposal
     * @param string $disposalMethod Method used for disposal
     * @param string $disposalReason Reason for disposal
     * @param bool $legalHoldVerified Whether legal hold was verified
     * @param string|null $witnessedBy Witness to disposal (optional)
     * @param array $documentMetadata Original document metadata
     * @param array $chainOfCustody Chain of custody records
     * @param string|null $notes Additional notes
     */
    public function __construct(
        public string $certificationId,
        public string $tenantId,
        public string $documentId,
        public string $documentType,
        public RetentionCategory $retentionCategory,
        public \DateTimeImmutable $documentCreatedAt,
        public \DateTimeImmutable $retentionExpiredAt,
        public \DateTimeImmutable $disposedAt,
        public string $disposedBy,
        public string $approvedBy,
        public string $disposalMethod,
        public string $disposalReason,
        public bool $legalHoldVerified,
        public ?string $witnessedBy = null,
        public array $documentMetadata = [],
        public array $chainOfCustody = [],
        public ?string $notes = null,
    ) {}

    /**
     * Get retention duration in years.
     */
    public function getRetentionDurationYears(): int
    {
        return $this->documentCreatedAt->diff($this->retentionExpiredAt)->y;
    }

    /**
     * Get days past retention expiration.
     */
    public function getDaysPastExpiration(): int
    {
        return $this->retentionExpiredAt->diff($this->disposedAt)->days;
    }

    /**
     * Check if disposal was timely (within 30 days of expiration).
     */
    public function wasTimelyDisposal(): bool
    {
        return $this->getDaysPastExpiration() <= 30;
    }

    /**
     * Check if disposal had a witness.
     */
    public function hasWitness(): bool
    {
        return $this->witnessedBy !== null;
    }

    /**
     * Check if disposal was properly authorized.
     */
    public function isProperlyAuthorized(): bool
    {
        // Disposal must have different approver than disposer
        return $this->approvedBy !== $this->disposedBy;
    }

    /**
     * Get regulatory basis for retention.
     */
    public function getRegulatoryBasis(): string
    {
        return $this->retentionCategory->getRegulatoryBasis();
    }

    /**
     * Check if meets compliance requirements.
     */
    public function meetsComplianceRequirements(): bool
    {
        return $this->legalHoldVerified
            && $this->isProperlyAuthorized()
            && $this->disposedAt >= $this->retentionExpiredAt;
    }

    /**
     * Get compliance issues if any.
     */
    public function getComplianceIssues(): array
    {
        $issues = [];

        if (!$this->legalHoldVerified) {
            $issues[] = 'Legal hold verification not completed';
        }

        if (!$this->isProperlyAuthorized()) {
            $issues[] = 'Improper authorization - disposer same as approver';
        }

        if ($this->disposedAt < $this->retentionExpiredAt) {
            $issues[] = 'Document disposed before retention period expired';
        }

        if ($this->retentionCategory->requiresLegalHoldCheck() && !$this->legalHoldVerified) {
            $issues[] = 'Legal hold check required but not verified';
        }

        return $issues;
    }

    /**
     * Generate certificate content.
     */
    public function generateCertificate(): array
    {
        return [
            'certificate_number' => $this->certificationId,
            'document_details' => [
                'document_id' => $this->documentId,
                'document_type' => $this->documentType,
                'created_date' => $this->documentCreatedAt->format('Y-m-d'),
                'metadata' => $this->documentMetadata,
            ],
            'retention_details' => [
                'category' => $this->retentionCategory->value,
                'regulatory_basis' => $this->getRegulatoryBasis(),
                'required_years' => $this->retentionCategory->getRetentionYears(),
                'actual_years' => $this->getRetentionDurationYears(),
                'expired_date' => $this->retentionExpiredAt->format('Y-m-d'),
            ],
            'disposal_details' => [
                'method' => $this->disposalMethod,
                'reason' => $this->disposalReason,
                'disposed_date' => $this->disposedAt->format('Y-m-d H:i:s'),
                'disposed_by' => $this->disposedBy,
                'approved_by' => $this->approvedBy,
                'witnessed_by' => $this->witnessedBy,
            ],
            'compliance_verification' => [
                'legal_hold_verified' => $this->legalHoldVerified,
                'properly_authorized' => $this->isProperlyAuthorized(),
                'timely_disposal' => $this->wasTimelyDisposal(),
                'meets_requirements' => $this->meetsComplianceRequirements(),
                'issues' => $this->getComplianceIssues(),
            ],
            'chain_of_custody' => $this->chainOfCustody,
            'notes' => $this->notes,
        ];
    }
}
