<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when customer credit limit is exceeded.
 */
class CreditLimitExceededException extends SalesException
{
    public static function forCustomer(string $customerId, float $orderTotal, float $creditLimit): self
    {
        return new self(
            "Order total ({$orderTotal}) exceeds customer credit limit ({$creditLimit}) " .
            "for customer '{$customerId}'."
        );
    }
}
