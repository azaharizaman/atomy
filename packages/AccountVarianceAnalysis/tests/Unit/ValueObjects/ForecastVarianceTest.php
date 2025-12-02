<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Tests\Unit\ValueObjects;

use Nexus\AccountVarianceAnalysis\ValueObjects\ForecastVariance;
use PHPUnit\Framework\TestCase;

final class ForecastVarianceTest extends TestCase
{
    public function test_constructs_with_valid_data(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-01',
            100000.00,
            95000.00,
            -5000.00,
            95.0
        );

        $this->assertInstanceOf(ForecastVariance::class, $forecastVariance);
    }

    public function test_gets_period_id(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-01',
            100000.00,
            95000.00,
            -5000.00,
            95.0
        );

        $this->assertSame('period-2024-01', $forecastVariance->getPeriodId());
    }

    public function test_gets_forecast(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-01',
            100000.00,
            95000.00,
            -5000.00,
            95.0
        );

        $this->assertSame(100000.00, $forecastVariance->getForecast());
    }

    public function test_gets_actual(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-01',
            100000.00,
            95000.00,
            -5000.00,
            95.0
        );

        $this->assertSame(95000.00, $forecastVariance->getActual());
    }

    public function test_gets_variance_amount(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-01',
            100000.00,
            95000.00,
            -5000.00,
            95.0
        );

        $this->assertSame(-5000.00, $forecastVariance->getVarianceAmount());
    }

    public function test_gets_forecast_accuracy(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-01',
            100000.00,
            95000.00,
            -5000.00,
            95.0
        );

        $this->assertSame(95.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_actual_exceeds_forecast(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-02',
            100000.00,
            110000.00,
            10000.00,
            110.0
        );

        $this->assertSame(100000.00, $forecastVariance->getForecast());
        $this->assertSame(110000.00, $forecastVariance->getActual());
        $this->assertSame(10000.00, $forecastVariance->getVarianceAmount());
        $this->assertSame(110.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_actual_below_forecast(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-03',
            150000.00,
            120000.00,
            -30000.00,
            80.0
        );

        $this->assertSame(150000.00, $forecastVariance->getForecast());
        $this->assertSame(120000.00, $forecastVariance->getActual());
        $this->assertSame(-30000.00, $forecastVariance->getVarianceAmount());
        $this->assertSame(80.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_perfect_forecast(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-04',
            100000.00,
            100000.00,
            0.00,
            100.0
        );

        $this->assertSame(100000.00, $forecastVariance->getForecast());
        $this->assertSame(100000.00, $forecastVariance->getActual());
        $this->assertSame(0.00, $forecastVariance->getVarianceAmount());
        $this->assertSame(100.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_zero_forecast(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-05',
            0.00,
            5000.00,
            5000.00,
            0.0
        );

        $this->assertSame(0.00, $forecastVariance->getForecast());
        $this->assertSame(5000.00, $forecastVariance->getActual());
        $this->assertSame(5000.00, $forecastVariance->getVarianceAmount());
    }

    public function test_handles_zero_actual(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-06',
            10000.00,
            0.00,
            -10000.00,
            0.0
        );

        $this->assertSame(10000.00, $forecastVariance->getForecast());
        $this->assertSame(0.00, $forecastVariance->getActual());
        $this->assertSame(-10000.00, $forecastVariance->getVarianceAmount());
        $this->assertSame(0.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_negative_forecast(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-07',
            -50000.00,
            -45000.00,
            5000.00,
            90.0
        );

        $this->assertSame(-50000.00, $forecastVariance->getForecast());
        $this->assertSame(-45000.00, $forecastVariance->getActual());
        $this->assertSame(5000.00, $forecastVariance->getVarianceAmount());
    }

    public function test_handles_negative_actual(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-08',
            -30000.00,
            -40000.00,
            -10000.00,
            133.33
        );

        $this->assertSame(-30000.00, $forecastVariance->getForecast());
        $this->assertSame(-40000.00, $forecastVariance->getActual());
        $this->assertSame(-10000.00, $forecastVariance->getVarianceAmount());
    }

    public function test_handles_high_accuracy(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-09',
            100000.00,
            99500.00,
            -500.00,
            99.5
        );

        $this->assertSame(99.5, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_low_accuracy(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-10',
            100000.00,
            50000.00,
            -50000.00,
            50.0
        );

        $this->assertSame(50.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_accuracy_over_100_percent(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-11',
            100000.00,
            150000.00,
            50000.00,
            150.0
        );

        $this->assertSame(150.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_handles_large_values(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-12',
            10000000.00,
            9500000.00,
            -500000.00,
            95.0
        );

        $this->assertSame(10000000.00, $forecastVariance->getForecast());
        $this->assertSame(9500000.00, $forecastVariance->getActual());
        $this->assertSame(-500000.00, $forecastVariance->getVarianceAmount());
    }

    public function test_handles_small_values(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-13',
            0.01,
            0.009,
            -0.001,
            90.0
        );

        $this->assertSame(0.01, $forecastVariance->getForecast());
        $this->assertSame(0.009, $forecastVariance->getActual());
        $this->assertSame(-0.001, $forecastVariance->getVarianceAmount());
    }

    public function test_handles_decimal_precision(): void
    {
        $forecastVariance = new ForecastVariance(
            'period-2024-14',
            12345.6789,
            12340.1234,
            -5.5555,
            99.955
        );

        $this->assertSame(12345.6789, $forecastVariance->getForecast());
        $this->assertSame(12340.1234, $forecastVariance->getActual());
        $this->assertSame(-5.5555, $forecastVariance->getVarianceAmount());
        $this->assertSame(99.955, $forecastVariance->getForecastAccuracy());
    }

    public function test_period_id_formats(): void
    {
        $forecastVariance1 = new ForecastVariance('2024-01', 100.0, 100.0, 0.0, 100.0);
        $forecastVariance2 = new ForecastVariance('2024-Q1', 100.0, 100.0, 0.0, 100.0);
        $forecastVariance3 = new ForecastVariance('FY2024-P01', 100.0, 100.0, 0.0, 100.0);
        $forecastVariance4 = new ForecastVariance('jan-2024', 100.0, 100.0, 0.0, 100.0);

        $this->assertSame('2024-01', $forecastVariance1->getPeriodId());
        $this->assertSame('2024-Q1', $forecastVariance2->getPeriodId());
        $this->assertSame('FY2024-P01', $forecastVariance3->getPeriodId());
        $this->assertSame('jan-2024', $forecastVariance4->getPeriodId());
    }

    public function test_quarterly_period(): void
    {
        $forecastVariance = new ForecastVariance(
            '2024-Q2',
            300000.00,
            310000.00,
            10000.00,
            103.33
        );

        $this->assertSame('2024-Q2', $forecastVariance->getPeriodId());
    }

    public function test_annual_period(): void
    {
        $forecastVariance = new ForecastVariance(
            'FY2024',
            1200000.00,
            1180000.00,
            -20000.00,
            98.33
        );

        $this->assertSame('FY2024', $forecastVariance->getPeriodId());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(ForecastVariance::class);
        
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_is_final(): void
    {
        $reflection = new \ReflectionClass(ForecastVariance::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function test_revenue_forecast_scenario(): void
    {
        $forecastVariance = new ForecastVariance(
            '2024-03',
            500000.00,
            525000.00,
            25000.00,
            105.0
        );

        $this->assertSame(500000.00, $forecastVariance->getForecast());
        $this->assertSame(525000.00, $forecastVariance->getActual());
        $this->assertSame(25000.00, $forecastVariance->getVarianceAmount());
        $this->assertSame(105.0, $forecastVariance->getForecastAccuracy());
    }

    public function test_expense_forecast_scenario(): void
    {
        $forecastVariance = new ForecastVariance(
            '2024-04',
            200000.00,
            185000.00,
            -15000.00,
            92.5
        );

        $this->assertSame(200000.00, $forecastVariance->getForecast());
        $this->assertSame(185000.00, $forecastVariance->getActual());
        $this->assertSame(-15000.00, $forecastVariance->getVarianceAmount());
        $this->assertSame(92.5, $forecastVariance->getForecastAccuracy());
    }
}
