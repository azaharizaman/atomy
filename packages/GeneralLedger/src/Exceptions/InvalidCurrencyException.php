<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Invalid Currency Exception
 * 
 * Thrown when an invalid currency code is provided.
 */
class InvalidCurrencyException extends GeneralLedgerException
{
    private string $currencyCode;

    public static function forCode(string $currencyCode): self
    {
        $exception = new self(sprintf('Invalid currency code: %s. Expected ISO 4217 format (e.g., USD, EUR)', $currencyCode));
        $exception->currencyCode = $currencyCode;

        return $exception;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
}
