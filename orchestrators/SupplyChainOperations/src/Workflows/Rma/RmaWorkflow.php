<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\Rma;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\SupplyChainOperations\Contracts\SupplyChainReceivableManagerInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class RmaWorkflow
{
    public function __construct(
        private SupplyChainStockManagerInterface $stockManager,
        private SupplyChainReceivableManagerInterface $receivableManager,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function initiateReturn(RmaRequest $request): RmaResult
    {
        $this->logger->info("Initiating RMA for Sales Order {$request->salesOrderId}");

        $rmaId = $this->generateRmaId();
        
        $this->auditLogger->log(
            logName: 'rma_initiated',
            description: "RMA {$rmaId} initiated for SO {$request->salesOrderId}"
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
            description: "RMA {$rma->rmaId} received at warehouse {$warehouseId}"
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
                productId: $item['product_id'],
                warehouseId: $item['warehouse_id'],
                adjustmentQty: $item['quantity'],
                reason: 'rma_restock'
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
            description: "RMA {$rma->rmaId} processed - Credit: {$creditAmount}"
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
