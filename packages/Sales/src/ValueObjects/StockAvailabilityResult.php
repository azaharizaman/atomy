<?php

declare(strict_types=1);

namespace Nexus\Sales\ValueObjects;

/**
 * Result of stock availability check for a sales order.
 */
final readonly class StockAvailabilityResult
{
    /**
     * @param array<string, LineItemAvailability> $lineItems Map of line ID to availability
     * @param string[] $unavailableLines Line IDs that have insufficient stock
     */
    public function __construct(
        public bool $isAvailable,
        public array $lineItems,
        public array $unavailableLines = [],
        public ?string $shortageMessage = null
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
