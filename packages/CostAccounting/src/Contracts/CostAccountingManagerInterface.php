<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\ValueObjects\CostCenterHierarchy;
use Nexus\CostAccounting\ValueObjects\ProductCostSnapshot;
use Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown;

/**
 * Cost Accounting Manager - Primary facade interface
 * 
 * Main entry point for all cost accounting operations including
 * cost center management, cost pool allocation, product costing,
 * and variance analysis.
 */
interface CostAccountingManagerInterface
{
    /**
     * Create a new cost center
     * 
     * @param array<string, mixed> $data Cost center data
     * @return CostCenter
     */
    public function createCostCenter(array $data): CostCenter;

    /**
     * Update an existing cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @param array<string, mixed> $data Updated cost center data
     * @return CostCenter
     */
    public function updateCostCenter(string $costCenterId, array $data): CostCenter;

    /**
     * Get cost center hierarchy
     * 
     * @param string|null $rootCostCenterId Root cost center identifier (null for all)
     * @return CostCenterHierarchy
     */
    public function getCostCenterHierarchy(?string $rootCostCenterId = null): CostCenterHierarchy;

    /**
     * Create a new cost pool
     * 
     * @param array<string, mixed> $data Cost pool data
     * @return CostPool
     */
    public function createCostPool(array $data): CostPool;

    /**
     * Allocate pool costs to receiving cost centers
     * 
     * @param string $poolId Cost pool identifier
     * @param string $periodId Fiscal period identifier
     * @return array<string, mixed>
     */
    public function allocatePoolCosts(string $poolId, string $periodId): array;

    /**
     * Calculate product cost
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @param string $costType Cost type (actual/standard)
     * @return ProductCost
     */
    public function calculateProductCost(
        string $productId,
        string $periodId,
        string $costType = 'standard'
    ): ProductCost;

    /**
     * Perform multi-level cost rollup
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @return ProductCostSnapshot
     */
    public function performCostRollup(string $productId, string $periodId): ProductCostSnapshot;

    /**
     * Calculate cost variances
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @return CostVarianceBreakdown
     */
    public function calculateVariances(string $productId, string $periodId): CostVarianceBreakdown;
}
