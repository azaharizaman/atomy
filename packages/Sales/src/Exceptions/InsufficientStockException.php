<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when insufficient stock is available.
 */
class InsufficientStockException extends SalesException
{
    public static function forProduct(string $productVariantId, float $requested, float $available): self
    {
        return new self(
            "Insufficient stock for product '{$productVariantId}'. " .
            "Requested: {$requested}, Available: {$available}."
        );
    }
}
