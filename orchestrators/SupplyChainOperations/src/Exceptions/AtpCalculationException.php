<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Exceptions;

/**
 * Exception thrown when ATP calculation fails.
 */
final class AtpCalculationException extends \RuntimeException
{
    public static function productNotFound(string $productId): self
    {
        return new self("Product {$productId} not found for ATP calculation");
    }

    public static function warehouseNotFound(string $warehouseId): self
    {
        return new self("Warehouse {$warehouseId} not found for ATP calculation");
    }

    public static function insufficientData(string $productId, string $reason): self
    {
        return new self("Insufficient data for ATP calculation for product {$productId}: {$reason}");
    }
}
