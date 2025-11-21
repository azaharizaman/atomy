<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Exceptions;

/**
 * Thrown when warehouse is not found
 */
final class WarehouseNotFoundException extends WarehouseException
{
    public static function withId(string $warehouseId): self
    {
        return new self("Warehouse not found: {$warehouseId}");
    }
}
