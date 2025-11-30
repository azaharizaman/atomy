<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when purchase order is not found.
 */
class PurchaseOrderNotFoundException extends ProcurementException
{
    public static function forId(string $id): self
    {
        return new self("Purchase order with ID '{$id}' not found.");
    }

    public static function forNumber(string $tenantId, string $number): self
    {
        return new self("Purchase order '{$number}' not found for tenant '{$tenantId}'.");
    }
}
