<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Matching tolerance configuration interface.
 */
interface MatchingToleranceInterface
{
    /**
     * Get quantity tolerance percentage.
     *
     * @return float Percentage (e.g., 5.0 for 5%)
     */
    public function getQtyTolerancePercent(): float;

    /**
     * Get price tolerance percentage.
     *
     * @return float Percentage (e.g., 2.0 for 2%)
     */
    public function getPriceTolerancePercent(): float;

    /**
     * Check if quantity variance is within tolerance.
     *
     * @param float $variancePercent Variance percentage
     * @return bool
     */
    public function isQtyWithinTolerance(float $variancePercent): bool;

    /**
     * Check if price variance is within tolerance.
     *
     * @param float $variancePercent Variance percentage
     * @return bool
     */
    public function isPriceWithinTolerance(float $variancePercent): bool;
}
