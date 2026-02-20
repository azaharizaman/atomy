<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Inventory Data Provider Interface
 * 
 * Integration contract for Nexus\Inventory package.
 * Provides material cost data for product costing.
 */
interface InventoryDataProviderInterface
{
    /**
     * Get standard cost for product
     */
    public function getStandardCost(string $productId, string $periodId): float;

    /**
     * Get actual cost for product
     */
    public function getActualCost(string $productId, string $periodId): float;

    /**
     * Get material cost for product
     */
    public function getMaterialCost(string $productId, string $periodId): float;

    /**
     * Get material quantity for product
     */
    public function getMaterialQuantity(string $productId): float;

    /**
     * Get material costs by cost element
     */
    public function getMaterialCostsByElement(
        string $productId,
        string $periodId
    ): array;

    /**
     * Get inventory valuation
     */
    public function getInventoryValuation(
        string $productId,
        string $periodId
    ): float;
}
