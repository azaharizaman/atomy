<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence\Support;

use DateTimeImmutable;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;

final readonly class StaticExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function getRate(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): ExchangeRate
    {
        return new ExchangeRate(
            $pair->fromCode,
            $pair->toCode,
            '1.0',
            $asOf ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'))
        );
    }

    public function getRates(array $pairs, ?DateTimeImmutable $asOf = null): array
    {
        $rates = [];

        foreach ($pairs as $pair) {
            if ($pair instanceof CurrencyPair) {
                $rates[$pair->toString()] = $this->getRate($pair, $asOf);
            }
        }

        return $rates;
    }

    public function supportsHistoricalRates(): bool
    {
        return true;
    }

    public function getProviderName(): string
    {
        return 'static-mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
