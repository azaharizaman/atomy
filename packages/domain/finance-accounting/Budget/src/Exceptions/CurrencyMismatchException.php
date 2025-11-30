<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

/**
 * Currency Mismatch Exception
 * 
 * Thrown when attempting to combine budgets or transactions in different currencies.
 */
final class CurrencyMismatchException extends BudgetException
{
    public function __construct(
        private readonly string $expectedCurrency,
        private readonly string $actualCurrency,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Currency mismatch: Expected %s, got %s',
            $expectedCurrency,
            $actualCurrency
        );
        parent::__construct($message, $code);
    }

    public function getExpectedCurrency(): string
    {
        return $this->expectedCurrency;
    }

    public function getActualCurrency(): string
    {
        return $this->actualCurrency;
    }
}
