<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event raised when maverick (off-contract) spend is detected.
 */
final readonly class MaverickSpendDetectedEvent
{
    /**
     * @param string $eventId Event identifier
     * @param string $tenantId Tenant context
     * @param string $documentType Document type (typically PURCHASE_ORDER)
     * @param string $documentId Document identifier
     * @param string $documentNumber Document number
     * @param Money $spendAmount Amount of maverick spend
     * @param string $vendorId Non-contracted vendor used
     * @param string $vendorName Vendor name
     * @param string $categoryId Spend category
     * @param string $categoryName Category name
     * @param string|null $preferredVendorId Contracted vendor that should have been used
     * @param string|null $preferredVendorName Preferred vendor name
     * @param string|null $contractId Contract that was bypassed
     * @param Money|null $contractPrice Contract price for comparison
     * @param Money|null $potentialSavings Potential savings if contract used
     * @param string $createdBy User who created maverick spend
     * @param string $detectionMethod How maverick spend was detected
     * @param string $severity Severity level (LOW, MEDIUM, HIGH)
     * @param string|null $justification User's justification (if provided)
     * @param bool $requiresFollowUp Whether follow-up action is required
     * @param \DateTimeImmutable $detectedAt When maverick spend was detected
     * @param array $metadata Additional detection metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $documentType,
        public string $documentId,
        public string $documentNumber,
        public Money $spendAmount,
        public string $vendorId,
        public string $vendorName,
        public string $categoryId,
        public string $categoryName,
        public ?string $preferredVendorId = null,
        public ?string $preferredVendorName = null,
        public ?string $contractId = null,
        public ?Money $contractPrice = null,
        public ?Money $potentialSavings = null,
        public ?string $createdBy = null,
        public string $detectionMethod = 'AUTOMATIC',
        public string $severity = 'MEDIUM',
        public ?string $justification = null,
        public bool $requiresFollowUp = true,
        public ?\DateTimeImmutable $detectedAt = null,
        public array $metadata = [],
    ) {
        $this->detectedAt = $detectedAt ?? new \DateTimeImmutable();
    }

    /**
     * Check if there is a preferred vendor available.
     */
    public function hasPreferredVendor(): bool
    {
        return $this->preferredVendorId !== null;
    }

    /**
     * Check if there is an active contract.
     */
    public function hasActiveContract(): bool
    {
        return $this->contractId !== null;
    }

    /**
     * Get price variance from contract (if available).
     */
    public function getPriceVariance(): ?Money
    {
        if ($this->contractPrice === null) {
            return null;
        }

        return $this->spendAmount->subtract($this->contractPrice);
    }

    /**
     * Get price variance percentage.
     */
    public function getPriceVariancePercentage(): ?float
    {
        if ($this->contractPrice === null || $this->contractPrice->isZero()) {
            return null;
        }

        $variance = $this->getPriceVariance();
        if ($variance === null) {
            return null;
        }

        return round(
            ($variance->getAmount() / $this->contractPrice->getAmount()) * 100,
            2,
        );
    }

    /**
     * Check if user provided justification.
     */
    public function hasJustification(): bool
    {
        return $this->justification !== null && trim($this->justification) !== '';
    }

    /**
     * Check if this is high severity.
     */
    public function isHighSeverity(): bool
    {
        return $this->severity === 'HIGH';
    }

    /**
     * Get maverick spend type.
     */
    public function getMaverickType(): string
    {
        if ($this->hasActiveContract()) {
            return 'CONTRACT_BYPASS';
        }

        if ($this->hasPreferredVendor()) {
            return 'PREFERRED_VENDOR_BYPASS';
        }

        return 'UNCATEGORIZED_VENDOR';
    }

    /**
     * Calculate compliance impact score (1-100).
     */
    public function getComplianceImpactScore(): int
    {
        $score = 50; // Base score

        // Amount-based adjustment
        if ($this->spendAmount->greaterThan(Money::of(100000, $this->spendAmount->getCurrency()))) {
            $score += 30;
        } elseif ($this->spendAmount->greaterThan(Money::of(10000, $this->spendAmount->getCurrency()))) {
            $score += 15;
        }

        // Contract bypass is more severe
        if ($this->hasActiveContract()) {
            $score += 20;
        }

        // Severity adjustment
        if ($this->severity === 'HIGH') {
            $score += 10;
        } elseif ($this->severity === 'LOW') {
            $score -= 15;
        }

        // Justification reduces score slightly
        if ($this->hasJustification()) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * Get event name for dispatcher.
     */
    public static function getEventName(): string
    {
        return 'procurement.maverick_spend_detected';
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => self::getEventName(),
            'tenant_id' => $this->tenantId,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'document_number' => $this->documentNumber,
            'spend_amount' => $this->spendAmount->getAmount(),
            'spend_currency' => $this->spendAmount->getCurrency(),
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'preferred_vendor_id' => $this->preferredVendorId,
            'preferred_vendor_name' => $this->preferredVendorName,
            'contract_id' => $this->contractId,
            'contract_price' => $this->contractPrice?->getAmount(),
            'potential_savings' => $this->potentialSavings?->getAmount(),
            'price_variance' => $this->getPriceVariance()?->getAmount(),
            'price_variance_percentage' => $this->getPriceVariancePercentage(),
            'created_by' => $this->createdBy,
            'detection_method' => $this->detectionMethod,
            'maverick_type' => $this->getMaverickType(),
            'severity' => $this->severity,
            'justification' => $this->justification,
            'requires_follow_up' => $this->requiresFollowUp,
            'compliance_impact_score' => $this->getComplianceImpactScore(),
            'detected_at' => $this->detectedAt->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}
