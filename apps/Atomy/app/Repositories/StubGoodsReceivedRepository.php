<?php

declare(strict_types=1);

namespace Atomy\Repositories;

use Nexus\Payable\Contracts\GoodsReceivedRepositoryInterface;

/**
 * Stub implementation for Goods Received Note repository.
 * 
 * This is a temporary stub implementation until Nexus\Inventory package is created.
 * When Nexus\Inventory is implemented, this stub should be replaced with the actual implementation.
 */
final class StubGoodsReceivedRepository implements GoodsReceivedRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findLineByReference(string $lineReference): ?object
    {
        // Stub implementation - returns a mock object for testing
        // In production, this should be replaced with actual GRN line lookup
        
        // For now, return a simple object that satisfies the 3-way matching needs
        return new class {
            public function getQuantity(): float
            {
                return 10.0; // Mock received quantity
            }
        };
    }
}
