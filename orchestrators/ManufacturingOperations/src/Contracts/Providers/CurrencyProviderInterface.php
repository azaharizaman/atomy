<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

use Nexus\ManufacturingOperations\DTOs\CurrencyCode;

interface CurrencyProviderInterface
{
    /**
     * Get the exchange rate for a currency pair at a given timestamp.
     */
    public function getExchangeRate(CurrencyCode $from, CurrencyCode $to, \DateTimeImmutable $atTime): string;

    /**
     * Convert an amount between currencies.
     */
    public function convertAmount(string $amount, CurrencyCode $from, CurrencyCode $to, \DateTimeImmutable $atTime): string;
}
