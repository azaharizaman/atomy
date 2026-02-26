<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class StockReservationResult
{
    public function __construct(
        public ?string $reservationId = null,
        public bool $success = false,
        public array $shortages = [],
    ) {
        if ($this->success && empty($this->reservationId)) {
            throw new \InvalidArgumentException("reservationId must be provided on success");
        }
    }
}
