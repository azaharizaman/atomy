<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

use Nexus\Common\ValueObjects\Money;

interface CurrencyConversionInterface
{
    public function convert(Money $amount, string $toCurrency): Money;

    public function getExchangeRate(string $fromCurrency, string $toCurrency): float;

    public function getBaseCurrency(): string;

    public function isSupported(string $currency): bool;
}
