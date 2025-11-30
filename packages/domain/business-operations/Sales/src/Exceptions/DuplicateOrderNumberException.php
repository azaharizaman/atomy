<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when duplicate order number is detected.
 */
class DuplicateOrderNumberException extends SalesException
{
    public static function forNumber(string $tenantId, string $orderNumber): self
    {
        return new self(
            "Order number '{$orderNumber}' already exists for tenant '{$tenantId}'. " .
            "Order numbers must be unique within a tenant."
        );
    }
}
