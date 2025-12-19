<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Payment\ValueObjects\ExchangeRateSnapshot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExchangeRateSnapshot::class)]
final class ExchangeRateSnapshotTest extends TestCase
{
    #[Test]
    public function it_creates_exchange_rate_snapshot(): void
    {
        $capturedAt = new DateTimeImmutable();
        
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: $capturedAt,
            provider: 'oanda',
            rateType: 'mid'
        );

        $this->assertSame('USD', $snapshot->sourceCurrency);
        $this->assertSame('MYR', $snapshot->targetCurrency);
        $this->assertSame('4.50', $snapshot->rate);
        $this->assertSame($capturedAt, $snapshot->capturedAt);
        $this->assertSame('oanda', $snapshot->provider);
        $this->assertSame('mid', $snapshot->rateType);
    }

    #[Test]
    public function it_creates_snapshot_with_optional_values(): void
    {
        $capturedAt = new DateTimeImmutable();
        
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'EUR',
            targetCurrency: 'GBP',
            rate: '0.85',
            capturedAt: $capturedAt
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->capturedAt);
        $this->assertNull($snapshot->provider);
        $this->assertNull($snapshot->rateType);
    }

    #[Test]
    public function it_throws_exception_for_zero_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange rate must be a positive number');

        new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '0',
            capturedAt: new DateTimeImmutable()
        );
    }

    #[Test]
    public function it_throws_exception_for_negative_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange rate must be a positive number');

        new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '-1.50',
            capturedAt: new DateTimeImmutable()
        );
    }

    #[Test]
    public function it_creates_same_currency_snapshot(): void
    {
        $snapshot = ExchangeRateSnapshot::sameCurrency('MYR');

        $this->assertSame('MYR', $snapshot->sourceCurrency);
        $this->assertSame('MYR', $snapshot->targetCurrency);
        $this->assertSame('1.000000', $snapshot->rate);
        $this->assertSame('system', $snapshot->provider);
        $this->assertSame('identity', $snapshot->rateType);
    }

    #[Test]
    public function it_captures_rate_static_factory(): void
    {
        $snapshot = ExchangeRateSnapshot::capture(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            provider: 'oanda',
            rateType: 'mid'
        );

        $this->assertSame('USD', $snapshot->sourceCurrency);
        $this->assertSame('MYR', $snapshot->targetCurrency);
        $this->assertSame('4.50', $snapshot->rate);
        $this->assertSame('oanda', $snapshot->provider);
        $this->assertSame('mid', $snapshot->rateType);
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->capturedAt);
    }

    #[Test]
    public function it_converts_amount_using_rate(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: new DateTimeImmutable()
        );

        $convertedAmount = $snapshot->convert('100');

        $this->assertSame('450.000000', $convertedAmount);
    }

    #[Test]
    public function it_converts_back_to_source_currency(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: new DateTimeImmutable()
        );

        $convertedBack = $snapshot->convertBack('450');

        $this->assertSame('100.000000', $convertedBack);
    }

    #[Test]
    public function it_gets_rate_as_float(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: new DateTimeImmutable()
        );

        $this->assertSame(4.50, $snapshot->getRateAsFloat());
    }

    #[Test]
    public function it_checks_same_currency(): void
    {
        $crossCurrency = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: new DateTimeImmutable()
        );

        $sameCurrency = ExchangeRateSnapshot::sameCurrency('MYR');

        $this->assertFalse($crossCurrency->isSameCurrency());
        $this->assertTrue($sameCurrency->isSameCurrency());
    }

    #[Test]
    public function it_gets_currency_pair(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: new DateTimeImmutable()
        );

        $this->assertSame('USD/MYR', $snapshot->getCurrencyPair());
    }

    #[Test]
    public function it_gets_inverse_rate(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.00',
            capturedAt: new DateTimeImmutable()
        );

        $this->assertSame('0.2500000000', $snapshot->getInverseRate());
    }

    #[Test]
    public function it_creates_inverse_snapshot(): void
    {
        $capturedAt = new DateTimeImmutable();
        
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.00',
            capturedAt: $capturedAt,
            provider: 'oanda',
            rateType: 'mid'
        );

        $inverse = $snapshot->inverse();

        $this->assertSame('MYR', $inverse->sourceCurrency);
        $this->assertSame('USD', $inverse->targetCurrency);
        $this->assertSame('0.2500000000', $inverse->rate);
        $this->assertSame('oanda', $inverse->provider);
        $this->assertSame('mid', $inverse->rateType);
        $this->assertSame($capturedAt, $inverse->capturedAt);
    }

    #[Test]
    public function it_serializes_to_array(): void
    {
        $capturedAt = new DateTimeImmutable('2024-01-15 10:30:00');
        
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: $capturedAt,
            provider: 'oanda',
            rateType: 'mid'
        );

        $array = $snapshot->toArray();

        $this->assertSame([
            'source_currency' => 'USD',
            'target_currency' => 'MYR',
            'rate' => '4.50',
            'captured_at' => '2024-01-15T10:30:00+00:00',
            'provider' => 'oanda',
            'rate_type' => 'mid',
        ], $array);
    }

    #[Test]
    public function it_deserializes_from_array(): void
    {
        $array = [
            'source_currency' => 'EUR',
            'target_currency' => 'GBP',
            'rate' => '0.85',
            'captured_at' => '2024-01-15T10:30:00+00:00',
            'provider' => 'ecb',
            'rate_type' => 'spot',
        ];

        $snapshot = ExchangeRateSnapshot::fromArray($array);

        $this->assertSame('EUR', $snapshot->sourceCurrency);
        $this->assertSame('GBP', $snapshot->targetCurrency);
        $this->assertSame('0.85', $snapshot->rate);
        $this->assertSame('2024-01-15T10:30:00+00:00', $snapshot->capturedAt->format(DateTimeImmutable::ATOM));
        $this->assertSame('ecb', $snapshot->provider);
        $this->assertSame('spot', $snapshot->rateType);
    }

    #[Test]
    public function it_deserializes_from_array_with_defaults(): void
    {
        $array = [
            'source_currency' => 'EUR',
            'target_currency' => 'GBP',
            'rate' => '0.85',
            'captured_at' => '2024-01-15T10:30:00+00:00',
        ];

        $snapshot = ExchangeRateSnapshot::fromArray($array);

        $this->assertNull($snapshot->provider);
        $this->assertNull($snapshot->rateType);
    }

    #[Test]
    public function it_maintains_precision_in_conversion(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'JPY',
            rate: '148.567',
            capturedAt: new DateTimeImmutable()
        );

        $converted = $snapshot->convert('100.50');

        // 100.50 * 148.567 = 14930.9835
        $this->assertSame('14930.983500', $converted);
    }

    #[Test]
    public function it_handles_high_precision_rates(): void
    {
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'BTC',
            targetCurrency: 'USD',
            rate: '45678.12345678',
            capturedAt: new DateTimeImmutable()
        );

        $this->assertSame('45678.12345678', $snapshot->rate);
    }

    #[Test]
    public function it_converts_back_to_source_currency_using_rate(): void
    {
        // Verify that convertBack correctly divides target amount by rate
        // to return the source currency amount (inverse of convert())
        $snapshot = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.50',
            capturedAt: new DateTimeImmutable()
        );

        // 450 MYR / 4.50 = 100 USD
        $result = $snapshot->convertBack('450');
        $this->assertSame('100.000000', $result);
    }

    #[Test]
    public function it_round_trips_through_serialization(): void
    {
        $original = new ExchangeRateSnapshot(
            sourceCurrency: 'USD',
            targetCurrency: 'MYR',
            rate: '4.5678',
            capturedAt: new DateTimeImmutable('2024-01-15 10:30:00'),
            provider: 'oanda',
            rateType: 'spot'
        );

        $restored = ExchangeRateSnapshot::fromArray($original->toArray());

        $this->assertSame($original->sourceCurrency, $restored->sourceCurrency);
        $this->assertSame($original->targetCurrency, $restored->targetCurrency);
        $this->assertSame($original->rate, $restored->rate);
        $this->assertSame($original->provider, $restored->provider);
        $this->assertSame($original->rateType, $restored->rateType);
        $this->assertSame(
            $original->capturedAt->format(DateTimeImmutable::ATOM),
            $restored->capturedAt->format(DateTimeImmutable::ATOM)
        );
    }
}
