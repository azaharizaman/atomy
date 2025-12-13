<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

/**
 * DTO representing a legal hold record.
 */
final readonly class LegalHoldData
{
    /**
     * @param string $holdId Legal hold identifier
     * @param string $tenantId Tenant context
     * @param string $holdName Hold name/reference
     * @param string $matterReference Legal matter reference
     * @param string $description Hold description
     * @param \DateTimeImmutable $effectiveFrom When hold became effective
     * @param \DateTimeImmutable|null $effectiveTo When hold ends (null = indefinite)
     * @param string $initiatedBy User who initiated hold
     * @param \DateTimeImmutable $initiatedAt When hold was initiated
     * @param string|null $releasedBy User who released hold (null if active)
     * @param \DateTimeImmutable|null $releasedAt When hold was released
     * @param string|null $releaseReason Reason for release
     * @param array $affectedDocumentTypes Document types under hold
     * @param array $affectedVendorIds Specific vendor IDs under hold
     * @param array $affectedPurchaseOrderIds Specific PO IDs under hold
     * @param array $custodians Users responsible for preserving documents
     * @param array $metadata Additional hold metadata
     */
    public function __construct(
        public string $holdId,
        public string $tenantId,
        public string $holdName,
        public string $matterReference,
        public string $description,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo,
        public string $initiatedBy,
        public \DateTimeImmutable $initiatedAt,
        public ?string $releasedBy = null,
        public ?\DateTimeImmutable $releasedAt = null,
        public ?string $releaseReason = null,
        public array $affectedDocumentTypes = [],
        public array $affectedVendorIds = [],
        public array $affectedPurchaseOrderIds = [],
        public array $custodians = [],
        public array $metadata = [],
    ) {}

    /**
     * Check if hold is currently active.
     */
    public function isActive(?\DateTimeImmutable $asOfDate = null): bool
    {
        if ($this->releasedAt !== null) {
            return false;
        }

        $asOfDate ??= new \DateTimeImmutable();

        if ($asOfDate < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $asOfDate > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    /**
     * Check if hold has been released.
     */
    public function isReleased(): bool
    {
        return $this->releasedAt !== null;
    }

    /**
     * Check if hold is indefinite (no end date).
     */
    public function isIndefinite(): bool
    {
        return $this->effectiveTo === null;
    }

    /**
     * Check if document type is affected by hold.
     */
    public function affectsDocumentType(string $documentType): bool
    {
        if (empty($this->affectedDocumentTypes)) {
            return true; // Empty array means all types
        }

        return in_array($documentType, $this->affectedDocumentTypes, true);
    }

    /**
     * Check if vendor is affected by hold.
     */
    public function affectsVendor(string $vendorId): bool
    {
        if (empty($this->affectedVendorIds)) {
            return true; // Empty array means all vendors
        }

        return in_array($vendorId, $this->affectedVendorIds, true);
    }

    /**
     * Check if PO is affected by hold.
     */
    public function affectsPurchaseOrder(string $purchaseOrderId): bool
    {
        if (empty($this->affectedPurchaseOrderIds)) {
            return false; // Empty array means no specific POs
        }

        return in_array($purchaseOrderId, $this->affectedPurchaseOrderIds, true);
    }

    /**
     * Get hold duration in days (or days since start if still active).
     */
    public function getDurationDays(?\DateTimeImmutable $asOfDate = null): int
    {
        $asOfDate ??= new \DateTimeImmutable();

        $endDate = $this->releasedAt ?? $asOfDate;

        return $this->effectiveFrom->diff($endDate)->days;
    }

    /**
     * Get remaining days (for time-limited holds).
     */
    public function getRemainingDays(?\DateTimeImmutable $asOfDate = null): ?int
    {
        if ($this->effectiveTo === null || $this->releasedAt !== null) {
            return null;
        }

        $asOfDate ??= new \DateTimeImmutable();

        if ($asOfDate >= $this->effectiveTo) {
            return 0;
        }

        return $asOfDate->diff($this->effectiveTo)->days;
    }

    /**
     * Get status label.
     */
    public function getStatus(): string
    {
        if ($this->isReleased()) {
            return 'RELEASED';
        }

        if ($this->isActive()) {
            return 'ACTIVE';
        }

        $now = new \DateTimeImmutable();

        if ($now < $this->effectiveFrom) {
            return 'PENDING';
        }

        return 'EXPIRED';
    }

    /**
     * Check if user is a custodian.
     */
    public function isCustodian(string $userId): bool
    {
        return in_array($userId, $this->custodians, true);
    }

    /**
     * Create released copy of hold.
     */
    public function withRelease(
        string $releasedBy,
        string $releaseReason,
        ?\DateTimeImmutable $releasedAt = null,
    ): self {
        return new self(
            holdId: $this->holdId,
            tenantId: $this->tenantId,
            holdName: $this->holdName,
            matterReference: $this->matterReference,
            description: $this->description,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $this->effectiveTo,
            initiatedBy: $this->initiatedBy,
            initiatedAt: $this->initiatedAt,
            releasedBy: $releasedBy,
            releasedAt: $releasedAt ?? new \DateTimeImmutable(),
            releaseReason: $releaseReason,
            affectedDocumentTypes: $this->affectedDocumentTypes,
            affectedVendorIds: $this->affectedVendorIds,
            affectedPurchaseOrderIds: $this->affectedPurchaseOrderIds,
            custodians: $this->custodians,
            metadata: $this->metadata,
        );
    }

    /**
     * Get hold summary.
     */
    public function getSummary(): array
    {
        return [
            'hold_id' => $this->holdId,
            'hold_name' => $this->holdName,
            'matter_reference' => $this->matterReference,
            'status' => $this->getStatus(),
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo?->format('Y-m-d'),
            'is_indefinite' => $this->isIndefinite(),
            'initiated_by' => $this->initiatedBy,
            'custodian_count' => count($this->custodians),
            'affected_document_types' => count($this->affectedDocumentTypes),
            'affected_vendors' => count($this->affectedVendorIds),
            'affected_pos' => count($this->affectedPurchaseOrderIds),
            'duration_days' => $this->getDurationDays(),
            'remaining_days' => $this->getRemainingDays(),
        ];
    }
}
