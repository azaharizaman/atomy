<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Duplicate vendor exception.
 */
class DuplicateVendorException extends PayableException
{
    public static function forCode(string $vendorCode): self
    {
        return new self("Vendor with code '{$vendorCode}' already exists.");
    }

    public static function forTaxId(string $taxId): self
    {
        return new self("Vendor with tax ID '{$taxId}' already exists.");
    }
}
