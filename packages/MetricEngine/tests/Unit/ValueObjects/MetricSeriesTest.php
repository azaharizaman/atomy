<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use PHPUnit\Framework\TestCase;

class MetricSeriesTest extends TestCase
{
    public function test_metric_series_rejects_empty_points(): void
    {
        $this->expectException(InsufficientDataException::class);
        $this->expectExceptionMessage('Metric series requires at least one point.');

        new MetricSeries('sales', []);
    }

    public function test_metric_series_accepts_valid_points(): void
    {
        $points = [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-02', 20),
        ];

        $series = new MetricSeries('sales', $points);

        $this->assertSame('sales', $series->name);
        $this->assertCount(2, $series->points);
    }

    public function test_metric_series_rejects_duplicate_period_keys(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Metric series period keys must be unique.');

        new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-01', 20),
        ]);
    }

    public function test_metric_series_rejects_out_of_order_period_keys(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Metric series period keys must be sorted in ascending order.');

        new MetricSeries('sales', [
            new TimeSeriesPoint('2026-02', 20),
            new TimeSeriesPoint('2026-01', 10),
        ]);
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
}
