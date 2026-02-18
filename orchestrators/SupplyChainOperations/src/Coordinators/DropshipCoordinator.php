<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\SupplyChainOperations\Contracts\ProcurementManagerInterface;
use Nexus\SupplyChainOperations\Contracts\SalesOrderInterface;
use Nexus\SupplyChainOperations\Contracts\SalesOrderLineInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Nexus\SupplyChainOperations\Contracts\DropshipCoordinatorInterface;
use Psr\Log\LoggerInterface;

final readonly class DropshipCoordinator implements DropshipCoordinatorInterface
{
    public function __construct(
        private ProcurementManagerInterface $procurementManager,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function createDropshipPo(
        SalesOrderInterface $salesOrder,
        array $lines,
        string $vendorId
    ): string {
        $tenantId = $salesOrder->getTenantId();
        $customerId = $salesOrder->getCustomerId();
        
        $poItems = [];
        foreach ($lines as $line) {
            $poItems[] = [
                'product_id' => $line->getProductVariantId(),
                'quantity' => $line->getQuantity(),
                'unit_cost' => null, 
                'metadata' => [
                    'sales_order_id' => $salesOrder->getId(),
                    'sales_order_line_id' => $line->getId(),
                    'is_dropship' => true
                ]
            ];
        }

        $poData = [
            'vendor_id' => $vendorId,
            'type' => 'DROPSHIP',
            'currency' => $salesOrder->getCurrencyCode(),
            'shipping_address' => $salesOrder->getShippingAddress(),
            'items' => $poItems,
            'notes' => "Dropship for SO #{$salesOrder->getOrderNumber()}",
            'metadata' => [
                'linked_sales_order' => $salesOrder->getId(),
                'customer_id' => $customerId
            ]
        ];

        $creatorId = $salesOrder->getConfirmedBy() ?? 'system'; 

        $purchaseOrder = $this->procurementManager->createDirectPO($tenantId, $creatorId, $poData);

        $this->auditLogger->log(
            logName: 'supply_chain_dropship_po_created',
            description: "Dropship PO {$purchaseOrder->getId()} created for SO {$salesOrder->getOrderNumber()}"
        );

        return $purchaseOrder->getId();
    }
}
