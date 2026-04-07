<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract for cost-accounting read operations used by orchestrators.
 */
interface CostAccountingManagerQueryInterface
{
    /**
     * Get cost pool projection by identifier.
     *
     * @return object Cost-pool projection
     */
    public function getCostPool(string $tenantId, string $poolId): object;

    /**
     * Get allocations attached to a cost pool.
     *
     * @return iterable<int, object> Allocation projections
     */
    public function getPoolAllocations(string $tenantId, string $poolId): iterable;

    /**
     * Get allocations posted in a specific period.
     *
     * @return iterable<int, object> Allocation projections
     */
    public function getPeriodAllocations(string $tenantId, string $periodId): iterable;

    /**
     * Get product cost projection.
     *
     * @return object Product-cost projection
     */
    public function getProductCost(string $tenantId, string $productId): object;

    /**
     * Get cost center projection by identifier.
     *
     * @return object Cost-center projection
     */
    public function getCostCenter(string $tenantId, string $costCenterId): object;
}
