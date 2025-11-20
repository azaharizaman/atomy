<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Matching result interface.
 *
 * Represents the result of 3-way matching for a vendor bill.
 */
interface MatchingResultInterface
{
    /**
     * Check if bill is fully matched.
     *
     * @return bool
     */
    public function isMatched(): bool;

    /**
     * Get matching status.
     *
     * @return string pending|matched|variance_review|failed
     */
    public function getStatus(): string;

    /**
     * Get variance details.
     *
     * @return array Array of variances by line
     */
    public function getVariances(): array;

    /**
     * Get line matching results.
     *
     * @return array<LineMatchingResultInterface>
     */
    public function getLineResults(): array;

    /**
     * Get total quantity variance percentage.
     *
     * @return float
     */
    public function getTotalQtyVariancePercent(): float;

    /**
     * Get total price variance percentage.
     *
     * @return float
     */
    public function getTotalPriceVariancePercent(): float;

    /**
     * Check if variance is within tolerance.
     *
     * @return bool
     */
    public function isWithinTolerance(): bool;
}
