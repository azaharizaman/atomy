<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use Nexus\Sales\ValueObjects\StockAvailabilityResult;

/**
 * Stock reservation service contract.
 * Integrates with Nexus\Inventory for real-time stock reservation.
 */
interface StockReservationInterface
{
    /**
     * Reserve stock for sales order line items.
     *
     * @param string $salesOrderId
     * @return array<string, string> Map of line ID to reservation ID
     * @throws \Nexus\Sales\Exceptions\InsufficientStockException
     */
    public function reserveStockForOrder(string $salesOrderId): array;

    /**
     * Release stock reservation when order is cancelled.
     *
     * @param string $salesOrderId
     * @return void
     */
    public function releaseStockReservation(string $salesOrderId): void;

    /**
     * Get all active reservations for a sales order.
     *
     * @param string $salesOrderId
     * @return array<string, array<string, mixed>> Map of reservation_id to reservation details
     */
    public function getOrderReservations(string $salesOrderId): array;

    /**
     * Check if stock is available for all line items in the order.
     *
     * @param string $salesOrderId
     * @return StockAvailabilityResult
     */
    public function checkStockAvailability(string $salesOrderId): StockAvailabilityResult;
}
