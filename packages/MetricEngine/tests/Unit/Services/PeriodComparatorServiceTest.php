<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\Services\PeriodComparatorService;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class PeriodComparatorServiceTest extends TestCase
{
    private PeriodComparatorService $service;

    protected function setUp(): void
    {
        $this->service = new PeriodComparatorService();
    }

    public function test_compares_periods_by_parsed_order(): void
    {
        $this->assertTrue($this->service->lessThanOrEqual('2026-Q2', '2026-Q4'));
        $this->assertFalse($this->service->lessThanOrEqual('2026-Q4', '2026-Q2'));
    }

    public function test_rejects_mixed_granularity_in_series(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Metric series period keys must use one granularity.');

        new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-Q2', 20),
        ]);
    }

    public function test_rejects_mixed_granularity_window(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Explicit window periods must use one granularity.');

        TimeWindow::explicitRange('2026-01', '2026-Q2');
    }
}
