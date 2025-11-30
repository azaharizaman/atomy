<?php

declare(strict_types=1);

namespace Nexus\Inventory\Exceptions;

/**
 * Thrown when lot is not found
 */
final class LotNotFoundException extends InventoryException
{
    public static function withId(string $lotId): self
    {
        return new self("Lot not found: {$lotId}");
    }
}
