<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class MaterialCost
{
    public function __construct(
        public string $productId,
        public string $totalCost,
        public CurrencyCode $currency,
        public float $quantity,
    ) {}
}
