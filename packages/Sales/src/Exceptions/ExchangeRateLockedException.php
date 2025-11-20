<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when attempting to modify order with locked exchange rate.
 */
class ExchangeRateLockedException extends SalesException
{
    public static function forOrder(string $orderId): self
    {
        return new self(
            "Cannot modify currency or exchange rate for order '{$orderId}'. " .
            "Exchange rate has been locked at order confirmation."
        );
    }
}
