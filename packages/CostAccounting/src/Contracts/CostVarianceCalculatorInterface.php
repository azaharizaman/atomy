<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown;

/**
 * Cost Variance Calculator Interface
 * 
 * Defines operations for calculating and analyzing cost variances.
 */
interface CostVarianceCalculatorInterface
{
    /**
     * Calculate variances for a product
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @param string $costCenterId Cost center identifier
     * @param string $tenantId Tenant identifier
     * @return CostVarianceBreakdown
     */
    public function calculate(string $productId, string $periodId, string $costCenterId, string $tenantId): CostVarianceBreakdown;

    /**
     * Check if variance exceeds investigation threshold
     * 
     * @param CostVarianceBreakdown $variance Variance breakdown
     * @param float $thresholdPercentage Threshold percentage
     * @return bool
     */
    public function exceedsThreshold(CostVarianceBreakdown $variance, float $thresholdPercentage): bool;
}
