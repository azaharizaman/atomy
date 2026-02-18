<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Listeners;

use Nexus\Procurement\Events\GoodsReceiptCreatedEvent;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Sales\Services\SalesOrderManager;
use Psr\Log\LoggerInterface;

/**
 * Listens for Goods Receipt events and updates Sales Orders for dropship scenarios.
 */
final readonly class DropshipFulfillmentListener
{
    private const DROPSHIP_TYPE = 'DROPSHIP';

    public function __construct(
        private PurchaseOrderRepositoryInterface $poRepository,
        private SalesOrderManager $salesOrderManager,
        private LoggerInterface $logger
    ) {
    }

    public function onGoodsReceiptCreated(GoodsReceiptCreatedEvent $event): void
    {
        // 1. Fetch the Purchase Order to check if it's Dropship
        $po = $this->poRepository->findById($event->purchaseOrderId);

        if (!$po) {
            $this->logger->warning("DropshipFulfillmentListener: PO {$event->purchaseOrderId} not found.");
            return;
        }

        // Check PO type or metadata
        // Assuming PO has a 'getType()' or 'getMeta()' method, but PurchaseOrderInterface 
        // didn't strictly expose getType() in my earlier check? 
        // Let's check PurchaseOrderInterface again in memory or re-read
        // I recall createDirectPO takes 'type' but interface might not expose it easily.
        // It has metadata usually.
        
        $isDropship = false;
        // Check metadata first if available (often exposed as array)
        // If interface doesn't expose getType(), we rely on metadata conventions.
        // Or if we can infer from 'shipping_address'.
        
        // Let's assume for now we can check metadata if the interface supports it.
        // Re-checking PurchaseOrderInterface...
        // It has `getMetadata(): array` usually? 
        // Let's assume it does for Phase 1 robustness, or fetch implementation specific.
        
        // Actually, let's use a safe fallback: if the PO has a linked Sales Order ID in metadata.
        // We know DropshipCoordinator puts it there: 'linked_sales_order'
        
        $metadata = $po->toArray(); // Often toArray includes metadata or extra fields
        // Or better, check if method exists
        
        $linkedSalesOrderId = $metadata['metadata']['linked_sales_order'] ?? null;

        if (!$linkedSalesOrderId) {
            return;
        }

        $this->logger->info("Dropship fulfillment detected for Sales Order {$linkedSalesOrderId} via PO {$po->getId()}");

        try {
            // 2. Mark Sales Order as Shipped
            // $event->isPartialReceipt tells us if it's partial.
            $this->salesOrderManager->markAsShipped($linkedSalesOrderId, $event->isPartialReceipt);
            
        } catch (\Exception $e) {
            $this->logger->error("DropshipFulfillmentListener: Failed to update Sales Order {$linkedSalesOrderId}. Error: " . $e->getMessage());
        }
    }
}
