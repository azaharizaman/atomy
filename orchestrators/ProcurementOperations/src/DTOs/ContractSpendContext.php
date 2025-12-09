<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context DTO containing contract/blanket PO spend information.
 *
 * Used by rules and services to validate release orders against contract limits.
 */
final readonly class ContractSpendContext
{
    /**
     * @param string $blanketPoId Blanket PO identifier
     * @param string $blanketPoNumber Human-readable blanket PO number
     * @param string $vendorId Associated vendor
     * @param int $maxAmountCents Maximum contract spend limit
     * @param int $currentSpendCents Current cumulative spend
     * @param int $pendingAmountCents Pending release orders not yet received
     * @param string $currency Currency code
     * @param \DateTimeImmutable $effectiveFrom Contract start date
     * @param \DateTimeImmutable $effectiveTo Contract end date
     * @param string $status Current contract status
     * @param int|null $minOrderAmountCents Minimum per-order amount
     * @param int $warningThresholdPercent Percentage for warning threshold
     * @param array<string> $allowedCategoryIds Categories allowed under this contract
     * @param int $releaseOrderCount Number of release orders created
     */
    public function __construct(
        public string $blanketPoId,
        public string $blanketPoNumber,
        public string $vendorId,
        public int $maxAmountCents,
        public int $currentSpendCents,
        public int $pendingAmountCents,
        public string $currency,
        public \DateTimeImmutable $effectiveFrom,
        public \DateTimeImmutable $effectiveTo,
        public string $status,
        public ?int $minOrderAmountCents = null,
        public int $warningThresholdPercent = 80,
        public array $allowedCategoryIds = [],
        public int $releaseOrderCount = 0,
    ) {}

    /**
     * Get remaining budget in cents.
     */
    public function getRemainingCents(): int
    {
        return max(0, $this->maxAmountCents - $this->currentSpendCents - $this->pendingAmountCents);
    }

    /**
     * Get effective available amount (excluding pending).
     */
    public function getEffectiveAvailableCents(): int
    {
        return max(0, $this->maxAmountCents - $this->currentSpendCents);
    }

    /**
     * Get percentage of budget utilized.
     */
    public function getPercentUtilized(): int
    {
        if ($this->maxAmountCents <= 0) {
            return 0;
        }
        return (int) (($this->currentSpendCents * 100) / $this->maxAmountCents);
    }

    /**
     * Check if the contract is approaching its spend limit.
     */
    public function isApproachingLimit(): bool
    {
        return $this->getPercentUtilized() >= $this->warningThresholdPercent;
    }

    /**
     * Check if the contract is within effective date range.
     */
    public function isWithinEffectivePeriod(\DateTimeImmutable $date): bool
    {
        return $date >= $this->effectiveFrom && $date <= $this->effectiveTo;
    }

    /**
     * Check if a release order amount would fit within remaining budget.
     */
    public function canAccommodate(int $amountCents): bool
    {
        return $amountCents <= $this->getRemainingCents();
    }

    /**
     * Check if an amount meets minimum order requirement.
     */
    public function meetsMinimumOrder(int $amountCents): bool
    {
        if ($this->minOrderAmountCents === null) {
            return true;
        }
        return $amountCents >= $this->minOrderAmountCents;
    }

    /**
     * Check if a category is allowed under this contract.
     */
    public function isCategoryAllowed(string $categoryId): bool
    {
        if (empty($this->allowedCategoryIds)) {
            return true; // No restrictions
        }
        return in_array($categoryId, $this->allowedCategoryIds, true);
    }

    /**
     * Get warning amount threshold in cents.
     */
    public function getWarningAmountCents(): int
    {
        return (int) (($this->maxAmountCents * $this->warningThresholdPercent) / 100);
    }
}
