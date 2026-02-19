<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Sales\Exceptions\StockReservationUnavailableException;
use Nexus\Sales\ValueObjects\StockAvailabilityResult;

/**
 * Null stock reservation implementation.
 *
 * This implementation throws StockReservationUnavailableException to indicate
 * that stock reservation requires the Inventory package adapter.
 *
 * Use this as the default binding when the Inventory package is not installed.
 * When Inventory is available, bind StockReservationInterface to
 * InventoryStockReservationAdapter in the adapter layer.
 */
final readonly class NullStockReservation implements StockReservationInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws StockReservationUnavailableException Always throws to indicate unavailable feature
     */
    public function reserveStockForOrder(string $salesOrderId): array
    {
        throw StockReservationUnavailableException::forOrder($salesOrderId);
    }

    /**
     * {@inheritDoc}
     *
     * @throws StockReservationUnavailableException Always throws to indicate unavailable feature
     */
    public function releaseStockReservation(string $salesOrderId): void
    {
        throw StockReservationUnavailableException::unavailable();
    }

    /**
     * {@inheritDoc}
     *
     * @throws StockReservationUnavailableException Always throws to indicate unavailable feature
     */
    public function getOrderReservations(string $salesOrderId): array
    {
        throw StockReservationUnavailableException::unavailable();
    }

    /**
     * {@inheritDoc}
     *
     * @throws StockReservationUnavailableException Always throws to indicate unavailable feature
     */
    public function checkStockAvailability(string $salesOrderId): StockAvailabilityResult
    {
        throw StockReservationUnavailableException::unavailable();
    }
}
