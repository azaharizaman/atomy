<?php

declare(strict_types=1);

namespace Atomy\Repositories;

use Nexus\Payable\Contracts\PurchaseOrderRepositoryInterface;

/**
 * Stub implementation for Purchase Order repository.
 * 
 * This is a temporary stub implementation until Nexus\Procurement package is created.
 * When Nexus\Procurement is implemented, this stub should be replaced with the actual implementation.
 */
final class StubPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findLineByReference(string $lineReference): ?object
    {
        // Stub implementation - returns a mock object for testing
        // In production, this should be replaced with actual PO line lookup
        
        // For now, return a simple object that satisfies the 3-way matching needs
        return new class {
            public function getQuantity(): float
            {
                return 10.0; // Mock quantity
            }

            public function getUnitPrice(): float
            {
                return 100.0; // Mock unit price
            }
        };
    }
}
