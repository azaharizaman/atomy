<?php

declare(strict_types=1);

namespace Nexus\Inventory\Exceptions;

/**
 * Thrown when attempting to allocate duplicate serial number within tenant
 */
final class DuplicateSerialException extends InventoryException
{
    public static function forSerial(string $serialNumber, string $tenantId): self
    {
        return new self(
            "Serial number '{$serialNumber}' already exists in tenant {$tenantId}"
        );
    }
}
