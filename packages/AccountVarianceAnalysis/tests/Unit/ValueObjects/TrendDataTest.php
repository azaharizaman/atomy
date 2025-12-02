<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Tests\Unit\ValueObjects;

use Nexus\AccountVarianceAnalysis\Enums\TrendDirection;
use Nexus\AccountVarianceAnalysis\ValueObjects\TrendData;
use PHPUnit\Framework\TestCase;

final class TrendDataTest extends TestCase
{
    public function test_constructs_with_valid_data(): void
    {
        $values = [100.0, 110.0, 120.0, 130.0];
        
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            $values,
            140.0
        );

        $this->assertInstanceOf(TrendData::class, $trendData);
    }

    public function test_gets_direction(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            [100.0, 110.0, 120.0],
            130.0
        );

        $this->assertSame(TrendDirection::INCREASING, $trendData->getDirection());
    }

    public function test_gets_slope(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            [100.0, 110.0, 120.0],
            130.0
        );

        $this->assertSame(10.5, $trendData->getSlope());
    }

    public function test_gets_r_squared(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            [100.0, 110.0, 120.0],
            130.0
        );

        $this->assertSame(0.95, $trendData->getRSquared());
    }

    public function test_gets_values(): void
    {
        $values = [100.0, 110.0, 120.0, 130.0];
        
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            $values,
            140.0
        );

        $this->assertSame($values, $trendData->getValues());
    }

    public function test_gets_forecast(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            [100.0, 110.0, 120.0],
            130.0
        );

        $this->assertSame(130.0, $trendData->getForecast());
    }

    public function test_gets_null_forecast_when_not_provided(): void
    {
        $trendData = new TrendData(
            TrendDirection::STABLE,
            0.0,
            0.50,
            [100.0, 100.0, 100.0]
        );

        $this->assertNull($trendData->getForecast());
    }

    public function test_is_significant_returns_true_when_r_squared_is_0_point_7(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            5.0,
            0.7,
            [100.0, 105.0, 110.0],
            115.0
        );

        $this->assertTrue($trendData->isSignificant());
    }

    public function test_is_significant_returns_true_when_r_squared_above_0_point_7(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.5,
            0.95,
            [100.0, 110.0, 120.0],
            130.0
        );

        $this->assertTrue($trendData->isSignificant());
    }

    public function test_is_significant_returns_false_when_r_squared_below_0_point_7(): void
    {
        $trendData = new TrendData(
            TrendDirection::VOLATILE,
            2.0,
            0.45,
            [100.0, 102.0, 98.0, 103.0],
            105.0
        );

        $this->assertFalse($trendData->isSignificant());
    }

    public function test_handles_decreasing_trend(): void
    {
        $trendData = new TrendData(
            TrendDirection::DECREASING,
            -5.5,
            0.88,
            [200.0, 195.0, 190.0, 185.0],
            180.0
        );

        $this->assertSame(TrendDirection::DECREASING, $trendData->getDirection());
        $this->assertSame(-5.5, $trendData->getSlope());
        $this->assertTrue($trendData->isSignificant());
    }

    public function test_handles_stable_trend(): void
    {
        $trendData = new TrendData(
            TrendDirection::STABLE,
            0.1,
            0.92,
            [100.0, 100.5, 99.8, 100.2],
            100.0
        );

        $this->assertSame(TrendDirection::STABLE, $trendData->getDirection());
        $this->assertSame(0.1, $trendData->getSlope());
        $this->assertTrue($trendData->isSignificant());
    }

    public function test_handles_volatile_trend(): void
    {
        $trendData = new TrendData(
            TrendDirection::VOLATILE,
            0.0,
            0.25,
            [100.0, 120.0, 80.0, 110.0, 90.0],
            105.0
        );

        $this->assertSame(TrendDirection::VOLATILE, $trendData->getDirection());
        $this->assertFalse($trendData->isSignificant());
    }

    public function test_handles_empty_values_array(): void
    {
        $trendData = new TrendData(
            TrendDirection::STABLE,
            0.0,
            0.0,
            [],
            null
        );

        $this->assertSame([], $trendData->getValues());
        $this->assertFalse($trendData->isSignificant());
    }

    public function test_handles_single_value(): void
    {
        $trendData = new TrendData(
            TrendDirection::STABLE,
            0.0,
            0.0,
            [100.0],
            100.0
        );

        $this->assertCount(1, $trendData->getValues());
        $this->assertSame([100.0], $trendData->getValues());
    }

    public function test_handles_zero_slope(): void
    {
        $trendData = new TrendData(
            TrendDirection::STABLE,
            0.0,
            0.95,
            [100.0, 100.0, 100.0],
            100.0
        );

        $this->assertSame(0.0, $trendData->getSlope());
        $this->assertTrue($trendData->isSignificant());
    }

    public function test_handles_perfect_r_squared(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.0,
            1.0,
            [100.0, 110.0, 120.0, 130.0],
            140.0
        );

        $this->assertSame(1.0, $trendData->getRSquared());
        $this->assertTrue($trendData->isSignificant());
    }

    public function test_handles_zero_r_squared(): void
    {
        $trendData = new TrendData(
            TrendDirection::VOLATILE,
            0.0,
            0.0,
            [100.0, 50.0, 150.0, 75.0],
            100.0
        );

        $this->assertSame(0.0, $trendData->getRSquared());
        $this->assertFalse($trendData->isSignificant());
    }

    public function test_handles_negative_values(): void
    {
        $trendData = new TrendData(
            TrendDirection::DECREASING,
            -10.0,
            0.85,
            [-50.0, -60.0, -70.0, -80.0],
            -90.0
        );

        $this->assertSame(-10.0, $trendData->getSlope());
        $this->assertSame(-90.0, $trendData->getForecast());
        $this->assertTrue($trendData->isSignificant());
    }

    public function test_handles_mixed_positive_negative_values(): void
    {
        $trendData = new TrendData(
            TrendDirection::VOLATILE,
            2.5,
            0.45,
            [-10.0, 5.0, -20.0, 15.0],
            10.0
        );

        $this->assertSame(2.5, $trendData->getSlope());
        $this->assertFalse($trendData->isSignificant());
    }

    public function test_handles_large_values(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            1000000.0,
            0.98,
            [1000000.0, 2000000.0, 3000000.0],
            4000000.0
        );

        $this->assertSame(1000000.0, $trendData->getSlope());
        $this->assertSame(4000000.0, $trendData->getForecast());
    }

    public function test_handles_decimal_precision(): void
    {
        $values = [100.123, 110.456, 120.789];
        
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            10.333,
            0.856789,
            $values,
            131.122
        );

        $this->assertSame(10.333, $trendData->getSlope());
        $this->assertSame(0.856789, $trendData->getRSquared());
        $this->assertSame(131.122, $trendData->getForecast());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(TrendData::class);
        
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_is_final(): void
    {
        $reflection = new \ReflectionClass(TrendData::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function test_boundary_r_squared_just_below_threshold(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            5.0,
            0.6999,
            [100.0, 105.0, 110.0],
            115.0
        );

        $this->assertFalse($trendData->isSignificant());
    }

    public function test_boundary_r_squared_just_above_threshold(): void
    {
        $trendData = new TrendData(
            TrendDirection::INCREASING,
            5.0,
            0.7001,
            [100.0, 105.0, 110.0],
            115.0
        );

        $this->assertTrue($trendData->isSignificant());
    }
}
