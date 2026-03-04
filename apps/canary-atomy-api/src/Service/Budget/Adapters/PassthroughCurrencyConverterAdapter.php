<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\CurrencyConverterInterface;
use Nexus\Common\ValueObjects\Money;

final class PassthroughCurrencyConverterAdapter implements CurrencyConverterInterface
{
    public function convert(Money $amount, string $toCurrency): Money
    {
        if ($amount->getCurrency() === $toCurrency) {
            return $amount;
        }

        // Canary fallback; replace with real FX provider integration.
        return Money::of($amount->getAmount(), $toCurrency);
    }
}
