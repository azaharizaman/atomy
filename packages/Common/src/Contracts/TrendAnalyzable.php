<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support trend analysis.
 *
 * Provides trend detection and change analysis capabilities for value objects
 * that need to be compared over time for business intelligence and reporting.
 *
 * **When to Use:**
 * - Financial metrics (revenue, profit, costs) needing trend visualization
 * - KPIs and performance indicators with period-over-period comparison
 * - Stock levels, sales figures, or any time-series numeric data
 * - Dashboard widgets showing growth/decline indicators
 * - Alerting systems detecting significant changes
 *
 * **Expected Behavior:**
 * - `getTrendDirection()` returns 'increasing', 'decreasing', or 'stable'
 * - `percentageChange()` returns the % change from previous value
 * - `isSignificantChange()` determines if change exceeds threshold
 * - Division by zero (previous = 0) should be handled gracefully
 * - Percentage change formula: ((current - previous) / previous) * 100
 *
 * @example Example implementation for Revenue:
 * ```php
 * final readonly class Revenue implements TrendAnalyzable
 * {
 *     public function __construct(private Money $amount) {}
 *
 *     public function getTrendDirection(TrendAnalyzable $previous): string
 *     {
 *         $change = $this->percentageChange($previous);
 *
 *         return match (true) {
 *             $change > 0.5  => 'increasing',  // > 0.5% threshold for "increasing"
 *             $change < -0.5 => 'decreasing',
 *             default        => 'stable',
 *         };
 *     }
 *
 *     public function percentageChange(TrendAnalyzable $previous): float
 *     {
 *         $prevAmount = $previous->amount->getAmount();
 *
 *         if ($prevAmount === 0) {
 *             return $this->amount->getAmount() > 0 ? 100.0 : 0.0;
 *         }
 *
 *         return (($this->amount->getAmount() - $prevAmount) / $prevAmount) * 100;
 *     }
 *
 *     public function isSignificantChange(TrendAnalyzable $previous, float $threshold): bool
 *     {
 *         return abs($this->percentageChange($previous)) >= $threshold;
 *     }
 * }
 *
 * // Usage
 * $thisMonth = new Revenue(Money::of(120000, 'MYR'));
 * $lastMonth = new Revenue(Money::of(100000, 'MYR'));
 *
 * $trend = $thisMonth->getTrendDirection($lastMonth);        // 'increasing'
 * $change = $thisMonth->percentageChange($lastMonth);        // 20.0 (%)
 * $significant = $thisMonth->isSignificantChange($lastMonth, 10.0); // true
 * ```
 */
interface TrendAnalyzable
{
    /**
     * Calculate trend direction (positive, negative, or neutral).
     * 
     * @return string 'increasing', 'decreasing', or 'stable'
     */
    public function getTrendDirection(self $previous): string;

    /**
     * Calculate percentage change from previous value.
     */
    public function percentageChange(self $previous): float;

    /**
     * Check if value represents significant change.
     */
    public function isSignificantChange(self $previous, float $threshold): bool;
}
