<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class StockReservationResult
{
    public function __construct(
        public string $reservationId,
        public bool $success,
        public array $shortages = [],
    ) {}
}
