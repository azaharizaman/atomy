<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Services\ComparisonService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class ComparisonServiceTest extends TestCase
{
    private ComparisonService $service;

    protected function setUp(): void
    {
        $this->service = new ComparisonService(new NumericValueService());
    }

    public function test_previous_period_comparison_returns_delta_and_pct_change(): void
    {
        $result = $this->service->previousPeriod(
            currentValue: 120,
            previousValue: 100,
            policy: new PrecisionPolicy(2)
        );

        $this->assertSame(20.0, $result->delta);
        $this->assertSame(0.2, $result->percentChange);
    }

    public function test_previous_period_fails_on_zero_previous_value(): void
    {
        $this->expectException(DivideByZeroMetricException::class);

        $this->service->previousPeriod(
            currentValue: 120,
            previousValue: 0,
            policy: new PrecisionPolicy(2)
        );
    }

    public function test_previous_period_with_negative_change(): void
    {
        $result = $this->service->previousPeriod(
            currentValue: 80,
            previousValue: 100,
            policy: new PrecisionPolicy(2)
        );

        $this->assertSame(-20.0, $result->delta);
        $this->assertSame(-0.2, $result->percentChange);
    }
}
