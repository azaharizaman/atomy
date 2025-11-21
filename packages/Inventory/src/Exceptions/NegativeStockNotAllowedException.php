<?php

declare(strict_types=1);

namespace Nexus\Inventory\Exceptions;

/**
 * Thrown when negative stock is attempted but not allowed by configuration
 */
final class NegativeStockNotAllowedException extends InventoryException
{
    public static function forProduct(string $productId, string $warehouseId): self
    {
        return new self(
            "Negative stock not allowed for product {$productId} in warehouse {$warehouseId}. " .
            "Check tenant configuration or product-level settings."
        );
    }
}
