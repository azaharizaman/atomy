<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

interface ExchangeRateServiceInterface
{
    public function getVolatility(string $baseCurrency, string $quoteCurrency, int $days): float;
}
