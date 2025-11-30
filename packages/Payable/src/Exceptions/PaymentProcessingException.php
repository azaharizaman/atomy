<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Payment processing exception.
 */
class PaymentProcessingException extends PayableException
{
    public static function insufficientFunds(string $paymentId): self
    {
        return new self("Payment '{$paymentId}' failed: Insufficient funds.");
    }

    public static function invalidAllocation(string $paymentId, string $reason): self
    {
        return new self("Payment '{$paymentId}' allocation failed: {$reason}");
    }

    public static function glPostingFailed(string $paymentId, string $reason): self
    {
        return new self("Payment '{$paymentId}' GL posting failed: {$reason}");
    }
}
