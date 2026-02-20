<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

/**
 * Cost Report Interface
 * 
 * Defines operations for cost reporting.
 */
interface CostReportInterface
{
    /**
     * Generate cost center summary report
     */
    public function generateCostCenterSummary(
        string $costCenterId,
        string $periodId
    ): array;

    /**
     * Generate cost allocation report
     */
    public function generateAllocationReport(
        string $poolId,
        string $periodId
    ): array;

    /**
     * Generate product cost report
     */
    public function generateProductCostReport(
        string $productId,
        string $periodId
    ): array;

    /**
     * Generate variance report
     */
    public function generateVarianceReport(
        string $periodId,
        ?string $costCenterId = null
    ): array;

    /**
     * Generate cost distribution report
     */
    public function generateCostDistributionReport(
        string $periodId,
        string $costCenterId
    ): array;

    /**
     * Generate period cost summary
     */
    public function generatePeriodSummary(string $periodId): array;
}
