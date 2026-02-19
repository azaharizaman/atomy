<?php

declare(strict_types=1);

namespace Nexus\Sales\ValueObjects;

/**
 * Result of stock availability check for a sales order.
 */
final class StockAvailabilityResult
{
    /**
     * @param array<string, LineItemAvailability> $lineItems Map of line ID to availability
     * @param string[] $unavailableLines Line IDs that have insufficient stock
     */
    public function __construct(
        public readonly bool $isAvailable,
        public readonly array $lineItems,
        public readonly array $unavailableLines = [],
        public readonly ?string $shortageMessage = null
    ) {}

    /**
     * Create a result indicating all stock is available.
     *
     * @param array<string, LineItemAvailability> $lineItems
     * @return self
     */
    public static function available(array $lineItems): self
    {
        return new self(
            isAvailable: true,
            lineItems: $lineItems,
            unavailableLines: [],
            shortageMessage: null
        );
    }

    /**
     * Create a result indicating stock is not available.
     *
     * @param array<string, LineItemAvailability> $lineItems
     * @param string[] $unavailableLines
     * @param string $message
     * @return self
     */
    public static function unavailable(
        array $lineItems,
        array $unavailableLines,
        string $message
    ): self {
        return new self(
            isAvailable: false,
            lineItems: $lineItems,
            unavailableLines: $unavailableLines,
            shortageMessage: $message
        );
    }
}

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
