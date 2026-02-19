<?php

declare(strict_types=1);

namespace Nexus\Sales\ValueObjects;

/**
 * Stock availability for a single line item.
 */
final class LineItemAvailability
{
    public function __construct(
        public readonly string $lineId,
        public readonly string $productVariantId,
        public readonly string $warehouseId,
        public readonly float $requestedQuantity,
        public readonly float $availableQuantity,
        public readonly bool $isAvailable
    ) {}
}
