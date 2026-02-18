<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderLineInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

/**
 * Coordinates Dropshipping flows.
 *
 * Converts Sales Order lines directly into Purchase Orders delivered to the customer.
 */
final readonly class DropshipCoordinator
{
    public function __construct(
        private ProcurementManagerInterface $procurementManager,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create a dropship Purchase Order for specific Sales Order lines.
     *
     * @param SalesOrderInterface $salesOrder
     * @param SalesOrderLineInterface[] $lines
     * @param string $vendorId
     * @return string The created Purchase Order ID
     */
    public function createDropshipPo(
        SalesOrderInterface $salesOrder,
        array $lines,
        string $vendorId
    ): string {
        $tenantId = $salesOrder->getTenantId();
        $customerId = $salesOrder->getCustomerId();
        
        // Prepare PO Items
        $poItems = [];
        foreach ($lines as $line) {
            $poItems[] = [
                'product_id' => $line->getProductVariantId(), // Assuming mapping exists or is same
                'quantity' => $line->getQuantity(),
                // Dropship usually implies buying at cost, not selling price.
                // In a real system, we'd fetch the vendor cost from a PriceList or ProductSupplier record.
                // For this coordinator, we rely on the ProcurementManager to resolve default cost if null,
                // or we arguably should assume the line has a linked cost.
                // We'll pass null for unit_cost to let Procurement logic handle standard cost lookup.
                'unit_cost' => null, 
                'metadata' => [
                    'sales_order_id' => $salesOrder->getId(),
                    'sales_order_line_id' => $line->getId(),
                    'is_dropship' => true
                ]
            ];
        }

        // Create Direct PO
        // Note: We need to pass the customer's shipping address as the delivery location.
        // The Procurement contract might support a 'delivery_address' override or 'drop_ship_address'.
        // Assuming standard PO data structure supports 'shipping_address'.
        
        $poData = [
            'vendor_id' => $vendorId,
            'type' => 'DROPSHIP',
            'currency' => $salesOrder->getCurrencyCode(), // Assuming we buy in same currency, or Procurment handles conversion
            'shipping_address' => $salesOrder->getShippingAddress(), // Crucial for dropship
            'items' => $poItems,
            'notes' => "Dropship for SO #{$salesOrder->getOrderNumber()}",
            'metadata' => [
                'linked_sales_order' => $salesOrder->getId(),
                'customer_id' => $customerId
            ]
        ];

        // Creator ID? We might need a system user or pass the user who confirmed the SO.
        // We'll assume the Sales Order's 'confirmedBy' or 'owner' is appropriate,
        // or a system automation user ID. using 'system' for now.
        $creatorId = $salesOrder->getConfirmedBy() ?? 'system'; 

        $purchaseOrder = $this->procurementManager->createDirectPO($tenantId, $creatorId, $poData);

        $this->auditLogger->log(
            logName: 'supply_chain_dropship_po_created',
            message: "Dropship PO {$purchaseOrder->getId()} created for SO {$salesOrder->getOrderNumber()}",
            context: [
                'sales_order_id' => $salesOrder->getId(),
                'purchase_order_id' => $purchaseOrder->getId(),
                'vendor_id' => $vendorId,
                'line_count' => count($lines)
            ]
        );

        return $purchaseOrder->getId();
    }
}
