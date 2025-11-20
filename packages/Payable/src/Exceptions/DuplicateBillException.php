<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Duplicate bill exception.
 */
class DuplicateBillException extends PayableException
{
    public static function forBillNumber(string $billNumber, string $vendorId): self
    {
        return new self("Vendor bill with number '{$billNumber}' already exists for vendor '{$vendorId}'.");
    }
}
