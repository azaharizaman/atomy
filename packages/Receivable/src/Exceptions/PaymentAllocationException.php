<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use RuntimeException;

/**
 * Payment Allocation Exception
 *
 * Thrown when payment allocation fails or produces invalid results.
 */
class PaymentAllocationException extends RuntimeException
{
    public static function insufficientAmount(float $paymentAmount, float $totalAllocated): self
    {
        return new self(
            sprintf(
                'Payment allocation failed: Total allocated amount (%.2f) exceeds payment amount (%.2f)',
                $totalAllocated,
                $paymentAmount
            )
        );
    }

    public static function noOpenInvoices(string $customerId): self
    {
        return new self("No open invoices found for customer {$customerId} to allocate payment");
    }

    public static function invalidAllocation(string $reason): self
    {
        return new self("Invalid payment allocation: {$reason}");
    }

    public static function allocationMismatch(float $paymentAmount, float $totalAllocated): self
    {
        return new self(
            sprintf(
                'Payment allocation mismatch: Payment amount (%.2f) does not match total allocated (%.2f)',
                $paymentAmount,
                $totalAllocated
            )
        );
    }
}
