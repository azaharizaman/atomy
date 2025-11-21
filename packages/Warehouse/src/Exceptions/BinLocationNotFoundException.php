<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Exceptions;

/**
 * Thrown when bin location is not found
 */
final class BinLocationNotFoundException extends WarehouseException
{
    public static function withId(string $binId): self
    {
        return new self("Bin location not found: {$binId}");
    }
}
