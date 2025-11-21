<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

/**
 * Stock reservation management interface
 */
interface ReservationManagerInterface
{
    /**
     * Reserve stock for order/work order
     * 
     * Publishes: StockReservedEvent
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity to reserve
     * @param string $referenceType Reference type (sales_order, work_order, etc.)
     * @param string $referenceId Reference document ID
     * @param int $ttlHours Time-to-live in hours (default 24)
     * @return string Reservation ID
     * @throws \Nexus\Inventory\Exceptions\InsufficientStockException
     */
    public function reserveStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $referenceType,
        string $referenceId,
        int $ttlHours = 24
    ): string;
    
    /**
     * Release reservation (manual or auto-expiry)
     * 
     * Publishes: ReservationExpiredEvent
     * 
     * @param string $reservationId Reservation identifier
     * @return void
     */
    public function releaseReservation(string $reservationId): void;
    
    /**
     * Fulfill reservation (consume reserved stock)
     * 
     * @param string $reservationId Reservation identifier
     * @return void
     */
    public function fulfillReservation(string $reservationId): void;
    
    /**
     * Clean up expired reservations
     * 
     * @return int Number of reservations expired
     */
    public function expireReservations(): int;
}
