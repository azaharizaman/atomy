<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for cost accounting data provider.
 *
 * This interface defines the data retrieval methods needed by the
 * CostAllocationCoordinator to perform cost accounting operations.
 *
 * Interface Segregation Compliance:
 * - Defines only data retrieval methods, no business logic
 * - Used by coordinators to aggregate data from multiple sources
 * - Implementation provided by adapters layer
 */
interface CostAccountingDataProviderInterface
{
    /**
     * Get cost pool summary for allocation.
     *
     * @param string $tenantId The tenant identifier
     * @param string $poolId The cost pool identifier
     * @return array<string, mixed> Cost pool summary data
     */
    public function getCostPoolSummary(string $tenantId, string $poolId): array;

    /**
     * Get allocated costs for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Allocated costs data
     */
    public function getAllocatedCosts(string $tenantId, string $periodId): array;

    /**
     * Get product cost data for costing.
     *
     * @param string $tenantId The tenant identifier
     * @param string $productId The product identifier
     * @return array<string, mixed> Product cost data
     */
    public function getProductCostData(string $tenantId, string $productId): array;

    /**
     * Get cost center summary for analysis.
     *
     * @param string $tenantId The tenant identifier
     * @param string $costCenterId The cost center identifier
     * @return array<string, mixed> Cost center summary data
     */
    public function getCostCenterSummary(string $tenantId, string $costCenterId): array;
}
