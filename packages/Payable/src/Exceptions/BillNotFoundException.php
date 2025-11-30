<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Bill not found exception.
 */
class BillNotFoundException extends PayableException
{
    public static function forId(string $billId): self
    {
        return new self("Vendor bill with ID '{$billId}' not found.");
    }

    public static function forBillNumber(string $billNumber): self
    {
        return new self("Vendor bill with number '{$billNumber}' not found.");
    }
}
