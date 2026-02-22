<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Interface for currency conversion operations.
 *
 * @since 1.0.0
 */
interface CurrencyConverterInterface
{
    /**
     * Convert an amount from one currency to another.
     *
     * @param string $amount The amount to convert (as string for precision)
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @return string The converted amount
     */
    public function convert(string $amount, string $fromCurrency, string $toCurrency): string;
}
