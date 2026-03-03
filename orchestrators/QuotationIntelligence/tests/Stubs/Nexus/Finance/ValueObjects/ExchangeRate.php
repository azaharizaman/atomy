<?php

declare(strict_types=1);

namespace Nexus\Finance\ValueObjects;

final readonly class ExchangeRate
{
    public function __construct(
        public string $fromCurrency,
        public string $toCurrency,
        public string $rate,
        public \DateTimeImmutable $effectiveDate
    ) {}

    public function getRate(): float
    {
        return (float)$this->rate;
    }
}
