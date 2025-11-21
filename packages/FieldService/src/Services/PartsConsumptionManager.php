<?php

declare(strict_types=1);

namespace Nexus\FieldService\Services;

use Nexus\FieldService\Contracts\PartsConsumptionRepositoryInterface;
use Nexus\FieldService\Events\PartsConsumedEvent;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Enums\IssueReason;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Parts Consumption Manager Service
 *
 * Handles parts/materials consumption with van stock waterfall logic.
 * Per BUS-FIE-0066: Deduct from technician van first, then warehouse.
 */
final readonly class PartsConsumptionManager
{
    public function __construct(
        private PartsConsumptionRepositoryInterface $consumptionRepository,
        private StockManagerInterface $stockManager,
        private WarehouseRepositoryInterface $warehouseRepository,
        private TenantContextInterface $tenantContext,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Record parts consumption for a work order.
     *
     * Executes waterfall logic:
     * 1. Check technician van stock
     * 2. Deduct available quantity from van
     * 3. Deduct remainder from primary warehouse
     */
    public function recordConsumption(
        string $workOrderId,
        string $productVariantId,
        float $quantity,
        string $technicianId
    ): void {
        $this->logger->info('Recording parts consumption', [
            'work_order_id' => $workOrderId,
            'product_variant_id' => $productVariantId,
            'quantity' => $quantity,
            'technician_id' => $technicianId,
        ]);

        // Get technician van warehouse ID
        $vanWarehouseId = $this->getTechnicianVanWarehouseId($technicianId);

        // Check van stock level
        $vanStockLevel = $this->getStockLevel($vanWarehouseId, $productVariantId);

        $consumedFromVan = 0.0;
        $consumedFromWarehouse = 0.0;

        if ($vanStockLevel >= $quantity) {
            // Sufficient stock in van - deduct from van only
            $this->deductStock($vanWarehouseId, $productVariantId, $quantity);
            $consumedFromVan = $quantity;
            
            $this->logger->info('Parts deducted from technician van', [
                'van_warehouse_id' => $vanWarehouseId,
                'quantity' => $quantity,
            ]);
        } else {
            // Partial or no stock in van - use waterfall
            if ($vanStockLevel > 0) {
                $this->deductStock($vanWarehouseId, $productVariantId, $vanStockLevel);
                $consumedFromVan = $vanStockLevel;
                
                $this->logger->info('Partial stock deducted from technician van', [
                    'van_warehouse_id' => $vanWarehouseId,
                    'quantity' => $vanStockLevel,
                ]);
            }

            // Deduct remainder from primary warehouse
            $remainingQuantity = $quantity - $vanStockLevel;
            $primaryWarehouseId = $this->getPrimaryWarehouseId();
            
            $this->deductStock($primaryWarehouseId, $productVariantId, $remainingQuantity);
            $consumedFromWarehouse = $remainingQuantity;
            
            $this->logger->info('Remaining stock deducted from primary warehouse', [
                'warehouse_id' => $primaryWarehouseId,
                'quantity' => $remainingQuantity,
            ]);
        }

        // Dispatch event
        $event = new PartsConsumedEvent(
            $workOrderId,
            $productVariantId,
            $quantity,
            $technicianId,
            new \DateTimeImmutable()
        );
        
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Get total parts cost for a work order.
     */
    public function getTotalPartsConsumed(string $workOrderId): array
    {
        return $this->consumptionRepository->findByWorkOrder($workOrderId);
    }

    /**
     * Get total parts cost for a work order.
     */
    public function getTotalPartsCost(string $workOrderId): float
    {
        return $this->consumptionRepository->getTotalCost($workOrderId);
    }

    /**
     * Get technician van warehouse ID.
     */
    private function getTechnicianVanWarehouseId(string $technicianId): string
    {
        // Van warehouses are identified by code pattern: VAN-{TECHNICIAN_ID}
        // The application layer should create these virtual warehouses for each technician
        return "VAN-{$technicianId}";
    }

    /**
     * Get primary warehouse ID.
     */
    private function getPrimaryWarehouseId(): string
    {
        // Get tenant ID from context
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? 'default';
        
        // Get the first active warehouse as primary
        // In production, this should be configurable via settings
        $warehouses = $this->warehouseRepository->findByTenant($tenantId);
        
        foreach ($warehouses as $warehouse) {
            if ($warehouse->isActive()) {
                return $warehouse->getId();
            }
        }
        
        // Fallback to a conventional primary warehouse code
        return 'PRIMARY';
    }

    /**
     * Get stock level for a product in a warehouse.
     */
    private function getStockLevel(string $warehouseId, string $productVariantId): float
    {
        return $this->stockManager->getCurrentStock($productVariantId, $warehouseId);
    }

    /**
     * Deduct stock from a warehouse.
     */
    private function deductStock(
        string $warehouseId,
        string $productVariantId,
        float $quantity
    ): void {
        // Use PRODUCTION as the closest match for field service consumption
        // In production, IssueReason enum should be extended with FIELD_SERVICE case
        $this->stockManager->issueStock(
            $productVariantId,
            $warehouseId,
            $quantity,
            IssueReason::PRODUCTION,
            null // Work order ID will be set in application layer via metadata
        );
    }
}
