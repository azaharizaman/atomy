<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class StockCheckRequest
{
    /**
     * @param array<string, float> $items Map of Product ID to Quantity
     */
    public function __construct(
        public array $items,
        public ?string $warehouseId = null,
    ) {
        if (empty($this->items)) {
            throw new \InvalidArgumentException("items array cannot be empty");
        }
        foreach ($this->items as $sku => $quantity) {
            if ($sku === '') {
                throw new \InvalidArgumentException("sku cannot be empty");
            }
            if ($quantity <= 0) {
                throw new \InvalidArgumentException("quantity must be greater than zero");
            }
        }
        if ($this->warehouseId !== null && empty($this->warehouseId)) {
            throw new \InvalidArgumentException("warehouseId cannot be empty if provided");
        }
    }
}
