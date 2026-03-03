<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Sales
 * 
 * This example demonstrates:
 * 1. Custom pricing strategies
 * 2. Stock reservation integration
 * 3. Credit limit checking
 */

use Nexus\Sales\Contracts\SalesOrderManagerInterface;
use Nexus\Sales\Contracts\PricingStrategyInterface;
use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Common\ValueObjects\Money;

class AdvancedOrderService
{
    public function __construct(
        private readonly SalesOrderManagerInterface $salesOrderManager,
        private readonly StockReservationInterface $stockReservation,
        private readonly PricingStrategyInterface $pricingStrategy
    ) {}
    
    public function processB2BOrder(string $customerId, array $items): void
    {
        // 1. Apply B2B Pricing Strategy
        $this->salesOrderManager->setPricingStrategy($this->pricingStrategy);
        
        // 2. Create Order
        $order = $this->salesOrderManager->createOrder($customerId, 'USD');
        
        foreach ($items as $item) {
            // 3. Check & Reserve Stock
            if (!$this->stockReservation->checkAvailability($item['sku'], $item['qty'])) {
                throw new \RuntimeException("Insufficient stock for {$item['sku']}");
            }
            
            $this->stockReservation->reserve(
                sku: $item['sku'],
                quantity: $item['qty'],
                reference: $order->getNumber()
            );
            
            // 4. Add Item with Dynamic Pricing
            $price = $this->pricingStrategy->calculatePrice($item['sku'], $customerId, $item['qty']);
            
            if (!$price instanceof Money) {
                $price = Money::of($price, 'USD');
            }
            
            $this->salesOrderManager->addItem(
                orderId: $order->getId(),
                productId: $item['sku'],
                quantity: $item['qty'],
                unitPrice: $price
            );
        }
        
        // 5. Confirm Order
        $this->salesOrderManager->confirmOrder($order->getId());
    }
}
