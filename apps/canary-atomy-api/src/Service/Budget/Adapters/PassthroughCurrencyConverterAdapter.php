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

        throw new \InvalidArgumentException(sprintf(
            'Currency conversion is not configured for canary runtime (%s -> %s).',
            $amount->getCurrency(),
            $toCurrency
        ));
    }
}
