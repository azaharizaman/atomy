<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Invalid bill state exception.
 */
class InvalidBillStateException extends PayableException
{
    public static function cannotPost(string $billId, string $currentStatus): self
    {
        return new self("Cannot post bill '{$billId}' with status '{$currentStatus}'. Bill must be approved.");
    }

    public static function cannotPay(string $billId, string $currentStatus): self
    {
        return new self("Cannot pay bill '{$billId}' with status '{$currentStatus}'. Bill must be posted.");
    }

    public static function cannotMatch(string $billId, string $currentStatus): self
    {
        return new self("Cannot match bill '{$billId}' with status '{$currentStatus}'.");
    }

    public static function alreadyPaid(string $billId): self
    {
        return new self("Bill '{$billId}' is already fully paid.");
    }
}
