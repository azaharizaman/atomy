<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\SalesReturnInterface;
use Nexus\Sales\Exceptions\SalesOrderNotFoundException;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Sales return (RMA) management service.
 *
 * This implementation handles return request validation and processing.
 * Phase 2: Integration with Nexus\Receivable for credit note creation.
 */
final readonly class SalesReturnManager implements SalesReturnInterface
{
    private const FLOAT_COMPARISON_EPSILON = 0.0001;

    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
        private LoggerInterface $logger
    ) {}

    /**
     * Create return order from sales order.
     *
     * @param string $salesOrderId
     * @param array $returnLineItems Array of ['line_id' => quantity, 'product_variant_id' => id, 'quantity' => qty]
     * @param string $returnReason
     * @return string Return order ID
     * @throws SalesOrderNotFoundException
     * @throws \InvalidArgumentException If return quantities exceed ordered quantities
     */
    public function createReturnOrder(
        string $salesOrderId,
        array $returnLineItems,
        string $returnReason
    ): string {
        // Validate sales order exists
        $salesOrder = $this->salesOrderRepository->findById($salesOrderId);

        // Validate order can have returns (not cancelled)
        if ($salesOrder->getStatus()->isFinal()) {
            throw new \InvalidArgumentException(
                "Cannot create return for order {$salesOrder->getOrderNumber()} - order is in final status"
            );
        }

        // Validate return line items
        $this->validateReturnLineItems($salesOrder, $returnLineItems);

        // Generate return order ID
        $returnOrderId = $this->generateReturnOrderId($salesOrder->getTenantId());

        // Log the return request
        $this->logger->info('Return order created', [
            'return_order_id' => $returnOrderId,
            'sales_order_id' => $salesOrderId,
            'sales_order_number' => $salesOrder->getOrderNumber(),
            'customer_id' => $salesOrder->getCustomerId(),
            'return_reason' => $returnReason,
            'line_items_count' => count($returnLineItems),
        ]);

        // Phase 2: Create credit note in Nexus\Receivable
        // For now, just log that this would happen
        $this->logger->info('Credit note creation deferred to Phase 2', [
            'return_order_id' => $returnOrderId,
            'sales_order_id' => $salesOrderId,
        ]);

        return $returnOrderId;
    }

    /**
     * Validate return line items against sales order.
     *
     * @throws \InvalidArgumentException
     */
    private function validateReturnLineItems(
        SalesOrderInterface $salesOrder,
        array $returnLineItems
    ): void {
        if (empty($returnLineItems)) {
            throw new \InvalidArgumentException('Return line items cannot be empty');
        }

        // Build map of order lines by product variant
        $orderLinesMap = [];
        foreach ($salesOrder->getLines() as $line) {
            $key = $line->getProductVariantId();
            if (!isset($orderLinesMap[$key])) {
                $orderLinesMap[$key] = [
                    'quantity' => 0,
                    'lines' => [],
                ];
            }
            $orderLinesMap[$key]['quantity'] += $line->getQuantity();
            $orderLinesMap[$key]['lines'][] = $line;
        }

        // Validate each return item
        foreach ($returnLineItems as $item) {
            $productVariantId = $item['product_variant_id'] ?? null;
            $returnQuantity = $item['quantity'] ?? 0;

            if ($productVariantId === null) {
                throw new \InvalidArgumentException('Product variant ID is required for return item');
            }

            if ($returnQuantity <= 0) {
                throw new \InvalidArgumentException(
                    "Return quantity must be positive for product {$productVariantId}"
                );
            }

            // Check if product exists in order
            if (!isset($orderLinesMap[$productVariantId])) {
                throw new \InvalidArgumentException(
                    "Product {$productVariantId} not found in order {$salesOrder->getOrderNumber()}"
                );
            }

            // Check return quantity doesn't exceed ordered quantity using tolerance-based comparison
            $orderedQuantity = $orderLinesMap[$productVariantId]['quantity'];
            if ($returnQuantity > $orderedQuantity + self::FLOAT_COMPARISON_EPSILON) {
                throw new \InvalidArgumentException(
                    "Return quantity ({$returnQuantity}) exceeds ordered quantity ({$orderedQuantity}) " .
                    "for product {$productVariantId}"
                );
            }
        }
    }

    /**
     * Generate a unique return order ID using sequence generator.
     * In Phase 2, this would create a proper ReturnOrder entity.
     */
    private function generateReturnOrderId(string $tenantId): string
    {
        return $this->sequenceGenerator->generate($tenantId, 'SALES_RETURN');
    }
}
