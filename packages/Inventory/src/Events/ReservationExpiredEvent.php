<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

/**
 * Reservation expired event
 * 
 * Published when reservation is released (manual or auto-expiry)
 */
final readonly class ReservationExpiredEvent
{
    public function __construct(
        public string $reservationId,
        public string $productId,
        public string $warehouseId,
        public float $quantity,
        public bool $autoExpired,
        public \DateTimeImmutable $expiredDate
    ) {
    }
}
