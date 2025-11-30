<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\StockReservationInterface;

/**
 * Stub stock reservation service (V1 implementation).
 * 
 * Throws exception indicating Inventory package is not installed.
 * Phase 2: Replace with real implementation from Nexus\Inventory.
 */
final readonly class StubStockReservation implements StockReservationInterface
{
    /**
     * {@inheritDoc}
     */
    public function reserveStockForOrder(string $salesOrderId): void
    {
        throw new \BadMethodCallException(
            'Stock reservation is not available in V1. ' .
            'This feature requires the Nexus\Inventory package. ' .
            'Please install and configure Nexus\Inventory to enable automatic stock reservation for sales orders.'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function releaseStockReservation(string $salesOrderId): void
    {
        throw new \BadMethodCallException(
            'Stock reservation is not available in V1. ' .
            'This feature requires the Nexus\Inventory package. ' .
            'Please install and configure Nexus\Inventory to enable automatic stock reservation for sales orders.'
        );
    }
}
