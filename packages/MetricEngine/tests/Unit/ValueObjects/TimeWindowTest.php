<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Enums\WindowType;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class TimeWindowTest extends TestCase
{
    public function test_fixed_rolling_window_rejects_non_positive_size(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Fixed rolling window size must be greater than zero.');

        TimeWindow::fixedRolling(0);
    }

    public function test_fixed_rolling_window_accepts_positive_size(): void
    {
        $window = TimeWindow::fixedRolling(3);

        $this->assertSame(WindowType::FIXED_ROLLING, $window->type);
        $this->assertSame(3, $window->size);
    }

    public function test_explicit_range_rejects_empty_start(): void
    {
        $this->expectException(InvalidWindowException::class);

        TimeWindow::explicitRange('', '2026-03');
    }

    public function test_explicit_range_rejects_empty_end(): void
    {
        $this->expectException(InvalidWindowException::class);

        TimeWindow::explicitRange('2026-01', '');
    }

    public function test_explicit_range_rejects_start_after_end(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Explicit window start period must be before or equal to end period.');

        TimeWindow::explicitRange('2026-03', '2026-01');
    }

    public function test_explicit_range_accepts_valid_periods(): void
    {
        $window = TimeWindow::explicitRange('2026-01', '2026-03');

        $this->assertSame(WindowType::EXPLICIT_RANGE, $window->type);
        $this->assertSame('2026-01', $window->startPeriod);
        $this->assertSame('2026-03', $window->endPeriod);
    }

    public function test_rejects_mixed_granularity_window(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Explicit window periods must use one granularity.');

        TimeWindow::explicitRange('2026-01', '2026-Q2');
    }
}
