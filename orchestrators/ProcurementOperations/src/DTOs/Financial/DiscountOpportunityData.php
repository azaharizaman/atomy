<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Data transfer object representing a discount opportunity.
 *
 * This DTO consolidates both early payment and volume discount opportunities
 * for unified analysis and optimization.
 */
final readonly class DiscountOpportunityData
{
    public function __construct(
        public string $opportunityId,
        public string $type, // 'EARLY_PAYMENT' or 'VOLUME_DISCOUNT'
        public string $vendorId,
        public string $vendorName,
        public ?string $invoiceId,
        public Money $invoiceAmount,
        public float $discountPercent,
        public Money $potentialSavings,
        public Money $investmentRequired,
        public float $annualizedRoi,
        public ?int $daysRemaining,
        public ?\DateTimeImmutable $expirationDate,
        public int $priority, // 1-5, 5 being highest
    ) {}

    /**
     * Check if the opportunity has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }

        return $this->expirationDate < new \DateTimeImmutable();
    }

    /**
     * Check if the opportunity is expiring soon (within given days).
     */
    public function isExpiringSoon(int $days = 5): bool
    {
        return $this->daysRemaining !== null && $this->daysRemaining <= $days;
    }

    /**
     * Check if this is a high-ROI opportunity (above threshold).
     */
    public function isHighRoi(float $threshold = 30.0): bool
    {
        return $this->annualizedRoi >= $threshold;
    }

    /**
     * Get the payback period in days.
     */
    public function getPaybackPeriodDays(): float
    {
        if ($this->potentialSavings->getAmount() <= 0) {
            return PHP_FLOAT_MAX;
        }

        $dailyReturn = $this->annualizedRoi / 365;
        return $dailyReturn > 0 ? 100 / $dailyReturn : PHP_FLOAT_MAX;
    }
}
