<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Exceptions;

/**
 * Thrown when a warehouse is not found
 * 
 * This exception is thrown when attempting to retrieve a warehouse by ID
 * that does not exist in the repository. Callers should catch this exception
 * to handle missing warehouse scenarios appropriately.
 */
final class WarehouseNotFoundException extends WarehouseException
{
    /**
     * Create exception for warehouse not found by ID
     * 
     * @param string $warehouseId Warehouse unique identifier that was not found
     * @return self
     */
    public static function withId(string $warehouseId): self
    {
        return new self("Warehouse not found: {$warehouseId}");
    }
}
