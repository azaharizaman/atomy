<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\JournalEntry\ValueObjects\ExchangeRate;
use PHPUnit\Framework\TestCase;

final class ExchangeRateTest extends TestCase
{
    public function test_convert_with_high_precision_rate_maintains_accuracy(): void
    {
        // Create a high-precision exchange rate
        $rate = ExchangeRate::of(
            'USD',
            'EUR',
            '0.85123456', // High-precision rate
            new \DateTimeImmutable('2024-01-01')
        );

        $usd = Money::of(100.00, 'USD'); // 100 USD

        $eur = $rate->convert($usd);

        // 100 USD * 0.85123456 = 85.123456 EUR
        // In minor units: 10000 * 0.85123456 = 8512.3456, rounds to 8512
        $this->assertSame('EUR', $eur->getCurrency());
        $this->assertSame(8512, $eur->getAmountInMinorUnits());
        $this->assertSame(85.12, $eur->getAmount());
    }

    public function test_convert_with_very_small_rate_maintains_precision(): void
    {
        // Cryptocurrency conversion with very small rate
        $rate = ExchangeRate::of(
            'USD',
            'BTC',
            '0.00001234', // Very small rate
            new \DateTimeImmutable('2024-01-01')
        );

        $usd = Money::of(1000000.00, 'USD'); // 1 million USD

        $btc = $rate->convert($usd);

        // 1000000 USD * 0.00001234 = 12.34 BTC
        // In minor units: 100000000 * 0.00001234 = 1234
        $this->assertSame('BTC', $btc->getCurrency());
        $this->assertSame(1234, $btc->getAmountInMinorUnits());
    }

    public function test_convert_with_large_rate_maintains_precision(): void
    {
        $rate = ExchangeRate::of(
            'USD',
            'JPY',
            '150.123456', // Large rate with precision
            new \DateTimeImmutable('2024-01-01')
        );

        $usd = Money::of(100.00, 'USD');

        $jpy = $rate->convert($usd);

        // 100 USD * 150.123456 = 15012.3456 JPY
        // In minor units: 10000 * 150.123456 = 1501234.56, rounds to 1501235
        $this->assertSame('JPY', $jpy->getCurrency());
        $this->assertSame(1501235, $jpy->getAmountInMinorUnits());
    }

    public function test_convert_with_high_precision_multi_decimal_rate(): void
    {
        // Use a rate with many decimal places to test high-precision handling
        $rate = ExchangeRate::of(
            'USD',
            'EUR',
            '0.12345678901234', // 14 decimal places
            new \DateTimeImmutable('2024-01-01')
        );

        $usd = Money::of(1000000.00, 'USD');

        $eur = $rate->convert($usd);

        // The conversion should use bcmath precision, not float precision
        $this->assertSame('EUR', $eur->getCurrency());
        // 100000000 * 0.12345678901234 = 12345678.901234 minor units
        // With 8 decimal scale: bcmul('100000000', '0.12345678901234', 8) = 12345678.90123400
        // Rounds to 12345679
        $this->assertSame(12345679, $eur->getAmountInMinorUnits());
    }
}
