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
use Nexus\Sales\ValueObjects\Money;

class AdvancedOrderService
{
    public function __construct(
        private readonly SalesOrderManagerInterface ,
        private readonly StockReservationInterface ,
        private readonly PricingStrategyInterface 
    ) {}
    
    public function processB2BOrder(string , array ): void
    {
        // 1. Apply B2B Pricing Strategy
        ->salesManager->setPricingStrategy(->pricing);
        
        // 2. Create Order
         = ->salesManager->createOrder(, 'USD');
        
        foreach ( as ) {
            // 3. Check & Reserve Stock
            if (!->inventory->checkAvailability(['sku'], ['qty'])) {
                throw new \RuntimeException("Insufficient stock for {['sku']}");
            }
            
            ->inventory->reserve(
                sku: ['sku'],
                quantity: ['qty'],
                reference: ->getNumber()
            );
            
            // 4. Add Item with Dynamic Pricing
             = ->pricing->calculatePrice(['sku'], , ['qty']);
            
            ->salesManager->addItem(
                orderId: ->getId(),
                productId: ['sku'],
                quantity: ['qty'],
                unitPrice: 
            );
        }
        
        // 5. Confirm Order
        ->salesManager->confirmOrder(->getId());
    }
}
