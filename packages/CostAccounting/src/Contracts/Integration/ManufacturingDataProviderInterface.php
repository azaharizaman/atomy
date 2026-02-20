<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Manufacturing Data Provider Interface
 * 
 * Integration contract for Nexus\Manufacturing package.
 * Provides production data and work order information.
 */
interface ManufacturingDataProviderInterface
{
    /**
     * Get standard labor cost for product
     */
    public function getStandardLaborCost(string $productId, string $periodId): float;

    /**
     * Get actual labor cost for product
     */
    public function getActualLaborCost(string $productId, string $periodId): float;

    /**
     * Get overhead rate for product
     */
    public function getOverheadRate(string $productId, string $periodId): float;

    /**
     * Get actual overhead cost for product
     */
    public function getActualOverheadCost(string $productId, string $periodId): float;

    /**
     * Get labor cost for product
     */
    public function getLaborCost(string $productId, string $periodId): float;

    /**
     * Get labor hours for product
     */
    public function getLaborHours(string $productId, string $periodId): float;

    /**
     * Get work order costs
     */
    public function getWorkOrderCosts(string $workOrderId): array;

    /**
     * Get production quantities
     */
    public function getProductionQuantity(
        string $productId,
        string $periodId
    ): float;

    /**
     * Get overhead allocation base
     */
    public function getOverheadAllocationBase(
        string $costCenterId,
        string $periodId
    ): float;

    /**
     * Get Bill of Materials for a product
     * @return array<int, array{product_id: string, quantity: float}>
     */
    public function getBillOfMaterials(string $productId): array;
}
