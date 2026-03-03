<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Sales
 * 
 * This example demonstrates:
 * 1. Custom pricing strategies
 * 2. Order-level stock reservation
 * 3. Credit limit checking
 */

use Nexus\Sales\Services\SalesOrderManager;
use Nexus\Sales\Contracts\PricingStrategyInterface;
use Nexus\Common\ValueObjects\Money;

class AdvancedOrderService
{
    public function __construct(
        private readonly SalesOrderManager $salesOrderManager,
        private readonly PricingStrategyInterface $pricingStrategy
    ) {}
    
    public function processB2BOrder(string $tenantId, string $customerId, array $items): void
    {
        // 1. Validate items and calculate pricing
        $orderLines = [];
        foreach ($items as $item) {
            // Guard against malformed items
            if (!isset($item['sku']) || empty($item['sku']) || !isset($item['qty']) || !is_numeric($item['qty'])) {
                continue;
            }

            $price = $this->pricingStrategy->calculatePrice($item['sku'], $customerId, (float)$item['qty']);
            
            if (!$price instanceof Money) {
                $price = Money::of($price, 'USD');
            }

            $orderLines[] = [
                'product_variant_id' => $item['sku'],
                'quantity' => (float)$item['qty'],
                'unit_price' => $price->getAmount(),
                'uom_code' => $item['uom'] ?? 'EA',
            ];
        }

        if (empty($orderLines)) {
            throw new \InvalidArgumentException("No valid items to process");
        }
        
        // 2. Create Order (Draft status)
        $order = $this->salesOrderManager->createOrder(
            tenantId: $tenantId,
            customerId: $customerId,
            lines: $orderLines,
            data: [
                'currency_code' => 'USD',
                'notes' => 'Bulk B2B Order',
            ]
        );
        
        try {
            // 3. Confirm Order 
            // This will automatically:
            // - Check credit limit
            // - Lock exchange rate
            // - Reserve stock for the entire order
            $this->salesOrderManager->confirmOrder($order->getId(), 'system-user');
            
            echo "Order {$order->getOrderNumber()} confirmed and stock reserved.\n";

        } catch (\Exception $e) {
            // 4. Handle failure (e.g., credit limit or stock issue)
            echo "Failed to confirm order: " . $e->getMessage() . "\n";
            
            // If we need to explicitly cancel and release any partial state
            $this->salesOrderManager->cancelOrder($order->getId(), "Order confirmation failed: " . $e->getMessage());
        }
    }
}
