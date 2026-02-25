<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class StockCheckRequest
{
    /**
     * @param array<string, float> $items Map of Product ID to Quantity
     */
    public function __construct(
        public array $items,
        public ?string $warehouseId = null,
    ) {}
}
