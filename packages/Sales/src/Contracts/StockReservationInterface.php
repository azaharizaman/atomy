<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Stock reservation service contract (stub for Nexus\Inventory integration).
 * V1: Stub implementation throws NotImplementedException.
 * Phase 2: Integrate with Nexus\Inventory for real-time stock reservation.
 */
interface StockReservationInterface
{
    /**
     * Reserve stock for sales order line items.
     *
     * @param string $salesOrderId
     * @return void
     * @throws \Nexus\Sales\Exceptions\InsufficientStockException
     * @throws \BadMethodCallException If Inventory package not installed
     */
    public function reserveStockForOrder(string $salesOrderId): void;

    /**
     * Release stock reservation when order is cancelled.
     *
     * @param string $salesOrderId
     * @return void
     * @throws \BadMethodCallException If Inventory package not installed
     */
    public function releaseStockReservation(string $salesOrderId): void;
}
