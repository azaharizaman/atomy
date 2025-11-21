<?php

declare(strict_types=1);

namespace App\Listeners\FieldService;

use Nexus\FieldService\Events\PartsConsumedEvent;
use Nexus\Inventory\Contracts\InventoryManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Inventory Deduction Listener for Parts Consumption
 *
 * When parts are consumed in a work order, deduct from inventory using waterfall logic:
 * 1. Try technician's van stock first
 * 2. If insufficient, deduct from central warehouse
 *
 * This implements BUS-FIE-0066: Waterfall parts consumption
 */
final readonly class DeductInventoryOnPartsConsumed
{
    public function __construct(
        private InventoryManagerInterface $inventoryManager,
        private WarehouseManagerInterface $warehouseManager,
        private LoggerInterface $logger
    ) {}

    public function handle(PartsConsumedEvent $event): void
    {
        try {
            $productVariantId = $event->getProductVariantId();
            $requiredQuantity = $event->getQuantity();
            $uom = $event->getUom();
            $technicianId = $event->getTechnicianId();

            // Step 1: Try to deduct from technician's van
            $vanWarehouseId = "VAN-{$technicianId}";
            $vanStock = $this->warehouseManager->getStockLevel($vanWarehouseId, $productVariantId, $uom);

            if ($vanStock >= $requiredQuantity) {
                // Sufficient stock in van
                $this->inventoryManager->deduct($vanWarehouseId, $productVariantId, $requiredQuantity, $uom, [
                    'reason' => 'field_service_consumption',
                    'work_order_id' => $event->getWorkOrderId(),
                    'source' => 'van',
                ]);

                $this->logger->info('Parts consumed from van stock', [
                    'work_order_id' => $event->getWorkOrderId(),
                    'product_variant_id' => $productVariantId,
                    'quantity' => $requiredQuantity,
                    'van_warehouse_id' => $vanWarehouseId,
                ]);

                return;
            }

            // Step 2: Partial deduction from van, rest from central warehouse
            if ($vanStock > 0) {
                $this->inventoryManager->deduct($vanWarehouseId, $productVariantId, $vanStock, $uom, [
                    'reason' => 'field_service_consumption',
                    'work_order_id' => $event->getWorkOrderId(),
                    'source' => 'van',
                ]);

                $remainingQuantity = $requiredQuantity - $vanStock;
            } else {
                $remainingQuantity = $requiredQuantity;
            }

            // Step 3: Deduct remaining from central warehouse
            $centralWarehouseId = $this->getCentralWarehouseId(); // Should be configurable
            $this->inventoryManager->deduct($centralWarehouseId, $productVariantId, $remainingQuantity, $uom, [
                'reason' => 'field_service_consumption',
                'work_order_id' => $event->getWorkOrderId(),
                'source' => 'warehouse',
            ]);

            $this->logger->info('Parts consumed with waterfall logic', [
                'work_order_id' => $event->getWorkOrderId(),
                'product_variant_id' => $productVariantId,
                'van_quantity' => $vanStock,
                'warehouse_quantity' => $remainingQuantity,
                'total_quantity' => $requiredQuantity,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to deduct inventory for parts consumption', [
                'work_order_id' => $event->getWorkOrderId(),
                'product_variant_id' => $event->getProductVariantId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getCentralWarehouseId(): string
    {
        // TODO: Make this configurable via Nexus\Setting
        return 'WAREHOUSE-CENTRAL';
    }
}
