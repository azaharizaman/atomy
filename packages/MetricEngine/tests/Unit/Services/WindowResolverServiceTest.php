<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Services\WindowResolverService;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class WindowResolverServiceTest extends TestCase
{
    private WindowResolverService $resolver;

    protected function setUp(): void
    {
        $this->resolver = new WindowResolverService();
    }

    public function test_fixed_rolling_window_returns_last_n_points(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-02', 20),
            new TimeSeriesPoint('2026-03', 30),
        ]);

        $resolved = $this->resolver->resolve($series, TimeWindow::fixedRolling(2));

        $this->assertSame(['2026-02', '2026-03'], array_map(
            static fn (TimeSeriesPoint $point): string => $point->periodKey,
            $resolved->points
        ));
    }

    public function test_fixed_rolling_window_fails_on_insufficient_data(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
        ]);

        $this->expectException(InsufficientDataException::class);

        $this->resolver->resolve($series, TimeWindow::fixedRolling(3));
    }

    public function test_explicit_range_filters_by_period_key(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-02', 20),
            new TimeSeriesPoint('2026-03', 30),
        ]);

        $resolved = $this->resolver->resolve($series, TimeWindow::explicitRange('2026-02', '2026-03'));

        $this->assertCount(2, $resolved->points);
    }

    public function test_explicit_range_fails_when_no_points_match(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
        ]);

        $this->expectException(InsufficientDataException::class);

        $this->resolver->resolve($series, TimeWindow::explicitRange('2026-05', '2026-06'));
    }

    public function test_fixed_rolling_window_size_equals_series_length(): void
    {
        $series = new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-02', 20),
        ]);

        $resolved = $this->resolver->resolve($series, TimeWindow::fixedRolling(2));

        $this->assertCount(2, $resolved->points);
    }
}
