<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\PeriodForecast;

/**
 * Test cases for PeriodForecast value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class PeriodForecastTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        $forecast = new PeriodForecast(
            periodId: 'period_001',
            amount: 1000.00,
            netBookValue: 9000.00,
            accumulatedDepreciation: 1000.00
        );

        $this->assertEquals('period_001', $forecast->periodId);
        $this->assertEquals(1000.00, $forecast->amount);
        $this->assertEquals(9000.00, $forecast->netBookValue);
        $this->assertEquals(1000.00, $forecast->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withValidData_createsForecast(): void
    {
        $forecast = PeriodForecast::create(
            periodId: 'period_001',
            amount: 1000.00,
            netBookValue: 9000.00,
            accumulatedDepreciation: 1000.00
        );

        $this->assertEquals('period_001', $forecast->periodId);
        $this->assertEquals(1000.00, $forecast->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function toArray_returnsCorrectArray(): void
    {
        $forecast = new PeriodForecast(
            periodId: 'period_001',
            amount: 1000.00,
            netBookValue: 9000.00,
            accumulatedDepreciation: 1000.00
        );

        $array = $forecast->toArray();
        
        $this->assertArrayHasKey('periodId', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('netBookValue', $array);
        $this->assertArrayHasKey('accumulatedDepreciation', $array);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function format_returnsFormattedString(): void
    {
        $forecast = new PeriodForecast(
            periodId: 'period_001',
            amount: 1000.00,
            netBookValue: 9000.00,
            accumulatedDepreciation: 1000.00
        );

        $formatted = $forecast->format();
        
        $this->assertStringContainsString('period_001', $formatted);
        $this->assertStringContainsString('1000.00', $formatted);
    }
}
