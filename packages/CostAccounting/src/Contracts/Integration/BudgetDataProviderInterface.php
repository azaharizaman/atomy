<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Budget Data Provider Interface
 * 
 * Integration contract for Nexus\Budget package.
 * Provides cost center budget information.
 */
interface BudgetDataProviderInterface
{
    /**
     * Get budget for cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $periodId Fiscal period identifier
     * @return array<string, mixed>|null
     */
    public function getBudgetForCostCenter(
        string $costCenterId,
        string $periodId
    ): ?array;

    /**
     * Get budget utilization
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $periodId Fiscal period identifier
     * @return float
     */
    public function getBudgetUtilization(
        string $costCenterId,
        string $periodId
    ): float;

    /**
     * Check budget availability
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $periodId Fiscal period identifier
     * @param float $amount Requested amount
     * @return bool
     */
    public function checkBudgetAvailability(
        string $costCenterId,
        string $periodId,
        float $amount
    ): bool;

    /**
     * Get budget variance
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $periodId Fiscal period identifier
     * @return float
     */
    public function getBudgetVariance(
        string $costCenterId,
        string $periodId
    ): float;
}
