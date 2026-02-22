<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\CostAllocationRequest;
use Nexus\FinanceOperations\DTOs\CostAllocationResult;
use Nexus\FinanceOperations\DTOs\ProductCostRequest;
use Nexus\FinanceOperations\DTOs\ProductCostResult;
use Nexus\FinanceOperations\DTOs\PeriodicAllocationRequest;
use Nexus\FinanceOperations\DTOs\PeriodicAllocationResult;

/**
 * Contract for cost allocation coordination operations.
 *
 * This coordinator handles cost accounting operations including cost pool
 * allocation, product costing, and periodic allocation workflows.
 *
 * Interface Segregation Compliance:
 * - Extends FinanceCoordinatorInterface for base coordinator contract
 * - Defines only cost allocation specific operations
 * - Uses DTOs for request/response to maintain type safety
 *
 * @see FinanceCoordinatorInterface
 */
interface CostAllocationCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Allocate costs from cost pools to cost centers.
     *
     * @param CostAllocationRequest $request The allocation request
     * @return CostAllocationResult The allocation result
     */
    public function allocateCosts(CostAllocationRequest $request): CostAllocationResult;

    /**
     * Calculate product costs including material, labor, and overhead.
     *
     * @param ProductCostRequest $request The product cost request
     * @return ProductCostResult The product cost result
     */
    public function calculateProductCost(ProductCostRequest $request): ProductCostResult;

    /**
     * Run periodic cost allocation for a period.
     *
     * @param PeriodicAllocationRequest $request The periodic allocation request
     * @return PeriodicAllocationResult The periodic allocation result
     */
    public function runPeriodicAllocation(PeriodicAllocationRequest $request): PeriodicAllocationResult;
}
