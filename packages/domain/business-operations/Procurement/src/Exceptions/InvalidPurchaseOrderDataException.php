<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when purchase order data is invalid.
 */
class InvalidPurchaseOrderDataException extends ProcurementException
{
    public static function noLines(): self
    {
        return new self("Purchase order must have at least one line item.");
    }

    public static function missingVendor(): self
    {
        return new self("Purchase order must have a vendor.");
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing from purchase order data.");
    }
}
