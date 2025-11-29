<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Sales
 * 
 * This example demonstrates:
 * 1. Creating a sales order
 * 2. Adding items to the order
 * 3. Calculating totals
 */

use Nexus\Sales\Contracts\SalesOrderManagerInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\ValueObjects\Money;

// ============================================
// Step 1: Initialize Manager
// ============================================

// In a real application, these would be injected via DI
class ExampleController
{
    public function __construct(
        private readonly SalesOrderManagerInterface 
    ) {}
    
    public function createOrder(): void
    {
        // ============================================
        // Step 2: Create Order
        // ============================================
        
         = ->salesManager->createOrder(
            customerId: 'CUST-001',
            currency: 'USD'
        );
        
        // ============================================
        // Step 3: Add Items
        // ============================================
        
        ->salesManager->addItem(
            orderId: ->getId(),
            productId: 'PROD-ABC',
            quantity: 2.0,
            unitPrice: new Money(1000, 'USD') // 0.00
        );
        
        ->salesManager->addItem(
            orderId: ->getId(),
            productId: 'PROD-XYZ',
            quantity: 1.0,
            unitPrice: new Money(5000, 'USD') // 0.00
        );
        
        // ============================================
        // Step 4: Finalize
        // ============================================
        
        echo "Order Created: " . ->getNumber() . "\n";
        echo "Total Amount: " . ->getTotalAmount()->format() . "\n";
    }
}
