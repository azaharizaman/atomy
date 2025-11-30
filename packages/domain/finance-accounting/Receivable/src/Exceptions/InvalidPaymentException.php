<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use RuntimeException;

/**
 * Invalid Payment Exception
 *
 * Thrown when payment data is invalid or cannot be processed.
 */
class InvalidPaymentException extends RuntimeException
{
    public static function negativeAmount(float $amount): self
    {
        return new self("Payment amount cannot be negative: {$amount}");
    }

    public static function zeroAmount(): self
    {
        return new self('Payment amount must be greater than zero');
    }

    public static function missingRequiredField(string $fieldName): self
    {
        return new self("Required payment field missing: {$fieldName}");
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency code: {$currency}");
    }

    public static function currencyMismatch(string $paymentCurrency, string $invoiceCurrency): self
    {
        return new self(
            "Currency mismatch: Payment in {$paymentCurrency}, invoice in {$invoiceCurrency}"
        );
    }
}
