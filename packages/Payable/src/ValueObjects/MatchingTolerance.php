<?php

declare(strict_types=1);

namespace Nexus\Payable\ValueObjects;

/**
 * Matching tolerance value object.
 */
final readonly class MatchingTolerance
{
    public function __construct(
        private float $qtyTolerancePercent,
        private float $priceTolerancePercent
    ) {
        if ($qtyTolerancePercent < 0.0 || $qtyTolerancePercent > 100.0) {
            throw new \InvalidArgumentException('Quantity tolerance must be between 0 and 100.');
        }
        if ($priceTolerancePercent < 0.0 || $priceTolerancePercent > 100.0) {
            throw new \InvalidArgumentException('Price tolerance must be between 0 and 100.');
        }
    }

    public function getQtyTolerancePercent(): float
    {
        return $this->qtyTolerancePercent;
    }

    public function getPriceTolerancePercent(): float
    {
        return $this->priceTolerancePercent;
    }

    public function isQtyWithinTolerance(float $variancePercent): bool
    {
        return abs($variancePercent) <= $this->qtyTolerancePercent;
    }

    public function isPriceWithinTolerance(float $variancePercent): bool
    {
        return abs($variancePercent) <= $this->priceTolerancePercent;
    }

    public function equals(self $other): bool
    {
        return $this->qtyTolerancePercent === $other->qtyTolerancePercent
            && $this->priceTolerancePercent === $other->priceTolerancePercent;
    }
}
