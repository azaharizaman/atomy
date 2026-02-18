<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\CrossDocking;

use Nexus\SupplyChainOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SupplyChainOperations\Contracts\SalesOrderInterface;
use Nexus\SupplyChainOperations\Contracts\SalesOrderLineInterface;
use Nexus\SupplyChainOperations\Contracts\StagingManagerInterface;
use Nexus\SupplyChainOperations\Contracts\StockReceivedEventInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class CrossDockingWorkflow
{
    public function __construct(
        private SalesOrderProviderInterface $salesOrderProvider,
        private StagingManagerInterface $stagingManager,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function handleStockReceived(StockReceivedEventInterface $event): void
    {
        $tenantId = $event->getTenantId();
        $productId = $event->getProductId();
        $warehouseId = $event->getWarehouseId();
        $receivedQuantity = $event->getQuantity();

        $orders = $this->salesOrderProvider->findByStatus($tenantId, 'confirmed');

        $availableQuantity = $receivedQuantity;
        $crossDockedOrders = [];

        foreach ($orders as $order) {
            if ($availableQuantity <= 0) {
                break;
            }

            $requiredQuantity = $this->calculateOrderRequirement($order, $productId);

            if ($requiredQuantity > 0) {
                $allocateQty = min($requiredQuantity, $availableQuantity);

                $this->processCrossDockAllocation(
                    $tenantId,
                    $warehouseId,
                    $productId,
                    $allocateQty,
                    $order->getId(),
                    $event->getGrnId()
                );

                $availableQuantity -= $allocateQty;
                $crossDockedOrders[] = [
                    'order_id' => $order->getId(),
                    'quantity' => $allocateQty,
                ];
            }
        }

        if (!empty($crossDockedOrders)) {
            $this->auditLogger->log(
                logName: 'supply_chain_cross_dock_completed',
                description: "Cross-docked {$receivedQuantity} units of {$productId} to " . count($crossDockedOrders) . " orders"
            );
        }
    }

    private function calculateOrderRequirement(SalesOrderInterface $order, string $productId): float
    {
        $required = 0.0;

        foreach ($order->getLines() as $line) {
            if ($line->getProductVariantId() === $productId) {
                $required += $line->getQuantity();
            }
        }

        return $required;
    }

    private function processCrossDockAllocation(
        string $tenantId,
        string $warehouseId,
        string $productId,
        float $quantity,
        string $orderId,
        ?string $grnId
    ): void {
        $this->logger->info("Cross-docking {$quantity} units of {$productId} for Order {$orderId}");

        $this->stagingManager->moveToStaging(
            $tenantId,
            $warehouseId,
            $productId,
            $quantity,
            $orderId,
            $grnId
        );

        $this->auditLogger->log(
            logName: 'supply_chain_cross_dock_initiated',
            description: "Cross-docking initiated for Order {$orderId} from GRN {$grnId}"
        );
    }
}
