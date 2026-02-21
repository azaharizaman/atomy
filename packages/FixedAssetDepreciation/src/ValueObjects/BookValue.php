<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

/**
 * Immutable value object representing the book value of an asset.
 */
final readonly class BookValue
{
    public function __construct(
        public float $cost,
        public float $salvageValue,
        public float $accumulatedDepreciation,
    ) {}

    /**
     * Get the net book value (cost - accumulated depreciation).
     */
    public function getNetBookValue(): float
    {
        return $this->cost - $this->accumulatedDepreciation;
    }

    /**
     * Get the depreciable amount (cost - salvage value).
     */
    public function getDepreciableAmount(): float
    {
        return $this->cost - $this->salvageValue;
    }

    /**
     * Check if asset is fully depreciated.
     */
    public function isFullyDepreciated(): bool
    {
        return $this->accumulatedDepreciation >= $this->getDepreciableAmount();
    }

    /**
     * Apply depreciation to this book value.
     */
    public function depreciate(DepreciationAmount $amount): self
    {
        $newAccumulated = $this->accumulatedDepreciation + $amount->getAmount();
        
        // Cap at depreciable amount
        if ($newAccumulated > $this->getDepreciableAmount()) {
            $newAccumulated = $this->getDepreciableAmount();
        }

        return new self(
            cost: $this->cost,
            salvageValue: $this->salvageValue,
            accumulatedDepreciation: $newAccumulated,
        );
    }

    /**
     * Revalue the asset with new cost and salvage.
     */
    public function revalue(float $newCost, float $newSalvage): self
    {
        return new self(
            cost: $newCost,
            salvageValue: $newSalvage,
            accumulatedDepreciation: $this->accumulatedDepreciation,
        );
    }

    /**
     * Get remaining depreciable amount.
     */
    public function getRemainingDepreciableAmount(): float
    {
        return max(0, $this->getDepreciableAmount() - $this->accumulatedDepreciation);
    }

    /**
     * Format as string.
     */
    public function format(): string
    {
        return sprintf(
            'Cost: %s, Salvage: %s, Accumulated: %s, Net: %s',
            number_format($this->cost, 2),
            number_format($this->salvageValue, 2),
            number_format($this->accumulatedDepreciation, 2),
            number_format($this->getNetBookValue(), 2)
        );
    }
}
