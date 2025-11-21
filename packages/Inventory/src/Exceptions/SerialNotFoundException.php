<?php

declare(strict_types=1);

namespace Nexus\Inventory\Exceptions;

/**
 * Thrown when serial number is not found
 */
final class SerialNotFoundException extends InventoryException
{
    public static function withNumber(string $serialNumber): self
    {
        return new self("Serial number not found: {$serialNumber}");
    }
}
