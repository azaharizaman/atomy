<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\Rma;

use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\QualityControl\Contracts\InspectionManagerInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

final readonly class RmaWorkflow
{
    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private WarehouseManagerInterface $warehouseManager,
        private StockManagerInterface $stockManager,
        private InspectionManagerInterface $inspectionManager,
        private ReceivableManagerInterface $receivableManager,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function initiateReturn(RmaRequest $request): RmaResult
    {
        $this->logger->info("Initiating RMA for Sales Order {$request->salesOrderId}");

        $rmaId = $this->generateRmaId();
        
        $this->auditLogger->log(
            logName: 'rma_initiated',
            message: "RMA {$rmaId} initiated for SO {$request->salesOrderId}",
            context: [
                'rma_id' => $rmaId,
                'sales_order_id' => $request->salesOrderId,
                'customer_id' => $request->customerId,
                'items' => $request->items,
                'reason' => $request->reason,
            ]
        );

        return new RmaResult(
            rmaId: $rmaId,
            salesOrderId: $request->salesOrderId,
            status: RmaStatus::PENDING_RECEIPT,
            items: $request->items,
            tenantId: $request->tenantId,
            customerId: $request->customerId
        );
    }

    public function receiveReturn(RmaResult $rma, string $warehouseId): RmaResult
    {
        $this->logger->info("Receiving RMA {$rma->rmaId} at warehouse {$warehouseId}");

        foreach ($rma->items as $item) {
            $this->stockManager->receiveReturn(
                tenantId: $rma->getTenantId(),
                productId: $item['product_id'],
                warehouseId: $warehouseId,
                quantity: $item['quantity'],
                reference: $rma->rmaId
            );
        }

        $this->auditLogger->log(
            logName: 'rma_received',
            message: "RMA {$rma->rmaId} received at warehouse {$warehouseId}",
            context: [
                'rma_id' => $rma->rmaId,
                'warehouse_id' => $warehouseId,
                'items' => $rma->items,
            ]
        );

        return $rma->withStatus(RmaStatus::PENDING_INSPECTION);
    }

    public function processInspection(RmaResult $rma, array $inspectionResults): RmaResult
    {
        $this->logger->info("Processing inspection results for RMA {$rma->rmaId}");

        $restockItems = [];
        $scrapItems = [];

        foreach ($inspectionResults as $result) {
            if ($result['condition'] === 'resellable') {
                $restockItems[] = $result;
            } else {
                $scrapItems[] = $result;
            }
        }

        foreach ($restockItems as $item) {
            $this->stockManager->adjustStock(
                tenantId: $rma->getTenantId(),
                productId: $item['product_id'],
                warehouseId: $item['warehouse_id'],
                quantity: $item['quantity'],
                reason: 'rma_restock',
                reference: $rma->rmaId
            );
        }

        foreach ($scrapItems as $item) {
            $this->stockManager->writeOff(
                tenantId: $rma->getTenantId(),
                productId: $item['product_id'],
                warehouseId: $item['warehouse_id'],
                quantity: $item['quantity'],
                reason: 'rma_scrap',
                reference: $rma->rmaId
            );
        }

        $creditAmount = $this->calculateCreditAmount($rma, $inspectionResults);

        $this->receivableManager->createCreditNote(
            tenantId: $rma->getTenantId(),
            customerId: $rma->getCustomerId(),
            salesOrderId: $rma->salesOrderId,
            amount: $creditAmount,
            reason: 'rma_credit',
            reference: $rma->rmaId
        );

        $this->auditLogger->log(
            logName: 'rma_processed',
            message: "RMA {$rma->rmaId} processed - Credit: {$creditAmount}",
            context: [
                'rma_id' => $rma->rmaId,
                'restock_items' => $restockItems,
                'scrap_items' => $scrapItems,
                'credit_amount' => $creditAmount,
            ]
        );

        return $rma->withStatus(RmaStatus::COMPLETED, [
            'restock_items' => $restockItems,
            'scrap_items' => $scrapItems,
            'credit_amount' => $creditAmount,
        ]);
    }

    private function generateRmaId(): string
    {
        return 'RMA-' . uniqid();
    }

    private function calculateCreditAmount(RmaResult $rma, array $inspectionResults): float
    {
        $amount = 0.0;
        foreach ($inspectionResults as $result) {
            $amount += ($result['unit_price'] ?? 0) * $result['quantity'];
        }
        return $amount;
    }
}
