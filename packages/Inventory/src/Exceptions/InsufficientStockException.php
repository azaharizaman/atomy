<?php

declare(strict_types=1);

namespace Nexus\Inventory\Exceptions;

/**
 * Thrown when attempting to issue stock exceeding available quantity
 */
final class InsufficientStockException extends InventoryException
{
    public static function forProduct(string $productId, string $warehouseId, float $requested, float $available): self
    {
        return new self(
            "Insufficient stock for product {$productId} in warehouse {$warehouseId}. " .
            "Requested: {$requested}, Available: {$available}"
        );
    }
}
