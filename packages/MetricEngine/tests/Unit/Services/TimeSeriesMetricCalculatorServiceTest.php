<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Services\ComparisonService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\TimeSeriesMetricCalculatorService;
use Nexus\MetricEngine\Services\PeriodComparatorService;
use Nexus\MetricEngine\Services\WindowResolverService;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class TimeSeriesMetricCalculatorServiceTest extends TestCase
{
    private TimeSeriesMetricCalculatorService $calculator;

    protected function setUp(): void
    {
        $numeric = new NumericValueService();

        $this->calculator = new TimeSeriesMetricCalculatorService(
            $numeric,
            new WindowResolverService(new PeriodComparatorService()),
            new ComparisonService($numeric)
        );
    }

    public function test_rolling_sum_and_rolling_avg(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-02', 20),
            new TimeSeriesPoint('2026-03', 30),
        ]);

        $this->assertSame(50.0, $this->calculator->rollingSum($series, TimeWindow::fixedRolling(2), PrecisionPolicy::default()));
        $this->assertSame(25.0, $this->calculator->rollingAvg($series, TimeWindow::fixedRolling(2), PrecisionPolicy::default()));
    }

    public function test_rolling_sum_with_explicit_range(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-02', 20),
            new TimeSeriesPoint('2026-03', 30),
        ]);

        $result = $this->calculator->rollingSum($series, TimeWindow::explicitRange('2026-02', '2026-03'), PrecisionPolicy::default());

        $this->assertSame(50.0, $result);
    }

    public function test_period_compare_returns_comparison_result(): void
    {
        $result = $this->calculator->periodCompare(
            currentValue: 120,
            previousValue: 100,
            policy: new PrecisionPolicy(2)
        );

        $this->assertSame(20.0, $result->delta);
        $this->assertSame(0.2, $result->percentChange);
    }
}
