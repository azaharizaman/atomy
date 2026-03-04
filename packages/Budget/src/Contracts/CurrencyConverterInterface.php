<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;

interface CurrencyConverterInterface
{
    public function convert(Money $amount, string $toCurrency): Money;
}
