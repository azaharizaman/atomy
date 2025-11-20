<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when sales order is not found.
 */
class SalesOrderNotFoundException extends SalesException
{
    public static function forId(string $id): self
    {
        return new self("Sales order with ID '{$id}' not found.");
    }

    public static function forNumber(string $tenantId, string $orderNumber): self
    {
        return new self("Sales order '{$orderNumber}' not found for tenant '{$tenantId}'.");
    }
}
