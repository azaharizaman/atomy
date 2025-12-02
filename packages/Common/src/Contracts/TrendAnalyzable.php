<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support trend analysis.
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
