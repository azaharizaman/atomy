<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

/**
 * Immutable value object representing the depreciation life of an asset.
 */
final readonly class DepreciationLife
{
    public function __construct(
        public int $usefulLifeYears,
        public int $usefulLifeMonths,
        public float $salvageValue,
        public float $totalDepreciableAmount,
    ) {}

    /**
     * Create from years only.
     */
    public static function fromYears(int $years, float $cost, float $salvage): self
    {
        return new self(
            usefulLifeYears: $years,
            usefulLifeMonths: $years * 12,
            salvageValue: $salvage,
            totalDepreciableAmount: $cost - $salvage,
        );
    }

    /**
     * Get total months of depreciation life.
     */
    public function getTotalMonths(): int
    {
        return $this->usefulLifeMonths;
    }

    /**
     * Get monthly depreciation amount.
     */
    public function getMonthlyDepreciation(): float
    {
        if ($this->usefulLifeMonths === 0) {
            return 0.0;
        }
        return $this->totalDepreciableAmount / $this->usefulLifeMonths;
    }

    /**
     * Get annual depreciation amount.
     */
    public function getAnnualDepreciation(): float
    {
        return $this->getMonthlyDepreciation() * 12;
    }

    /**
     * Check if life is valid for depreciation.
     */
    public function isValid(): bool
    {
        return $this->usefulLifeMonths > 0 && $this->totalDepreciableAmount > 0;
    }
}
