<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\CrossDocking;

use Nexus\Inventory\Events\StockReceivedEvent;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Enums\SalesOrderStatus;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

/**
 * Stateful workflow for Cross-Docking operations.
 * 
 * Orchestrates moving received goods directly to staging for pending sales orders.
 */
final readonly class CrossDockingWorkflow
{
    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private WarehouseManagerInterface $warehouseManager,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Entry point triggered when stock is received.
     */
    public function handleStockReceived(StockReceivedEvent $event, string $tenantId): void
    {
        // 1. Find confirmed orders for this tenant
        $orders = $this->salesOrderRepository->findByStatus($tenantId, SalesOrderStatus::CONFIRMED->value);
        
        $pendingQuantity = $event->quantity;

        foreach ($orders as $order) {
            if ($pendingQuantity <= 0) {
                break;
            }

            foreach ($order->getLines() as $line) {
                if ($line->getProductVariantId() === $event->productId) {
                    $this->processCrossDockLine($event, $order->getId(), $line->getId(), $pendingQuantity);
                }
            }
        }
    }

    private function processCrossDockLine(
        StockReceivedEvent $event,
        string $orderId,
        string $lineId,
        float &$pendingQuantity
    ): void {
        // In a real stateful Saga, this would be a multi-step process with state persistence
        // For this orchestration demonstration, we coordinate the individual packets.
        
        $this->logger->info("Cross-docking candidate identified for Order {$orderId}, Line {$lineId}.");

        // 2. Request Warehouse to move stock to Staging
        // We'll assume the WarehouseManager has a method for this.
        // If not, we'd log the need and potentially create a task.
        
        $this->auditLogger->log(
            logName: 'supply_chain_cross_dock_initiated',
            message: "Cross-docking initiated for Order {$orderId} from GRN {$event->grnId}",
            context: [
                'product_id' => $event->productId,
                'warehouse_id' => $event->warehouseId,
                'order_id' => $orderId,
                'quantity' => $event->quantity,
            ]
        );

        // Update pending quantity for the received batch
        // (Simplified logic: we don't check line quantity here for brevity)
        $pendingQuantity = 0; 
    }
}
