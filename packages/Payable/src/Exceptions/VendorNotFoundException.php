<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Vendor not found exception.
 */
class VendorNotFoundException extends PayableException
{
    public static function forId(string $vendorId): self
    {
        return new self("Vendor with ID '{$vendorId}' not found.");
    }

    public static function forCode(string $vendorCode): self
    {
        return new self("Vendor with code '{$vendorCode}' not found.");
    }
}
