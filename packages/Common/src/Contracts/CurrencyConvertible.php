<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support currency conversion.
 */
interface CurrencyConvertible
{
    /**
     * Convert to another currency using exchange rate.
     * 
     * @param string $toCurrency Target currency code
     * @param float $exchangeRate Exchange rate from current to target currency
     * @return static New instance in the target currency
     */
    public function convertToCurrency(string $toCurrency, float $exchangeRate): static;

    /**
     * Get the currency code.
     */
    public function getCurrency(): string;
}
