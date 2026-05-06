<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class ScalarMetricCalculatorServiceTest extends TestCase
{
    private ScalarMetricCalculatorService $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ScalarMetricCalculatorService(new NumericValueService());
    }

    public function test_sum_avg_min_max_and_count(): void
    {
        $policy = PrecisionPolicy::default();
        $values = [1, 2, 3, 4, 5];

        $this->assertSame(15.0, $this->calculator->sum($values, $policy));
        $this->assertSame(3.0, $this->calculator->avg($values, $policy));
        $this->assertSame(1.0, $this->calculator->min($values, $policy));
        $this->assertSame(5.0, $this->calculator->max($values, $policy));
        $this->assertSame(5.0, $this->calculator->count($values, $policy));
    }

    public function test_sum_with_empty_array_throws(): void
    {
        $this->expectException(InsufficientDataException::class);

        $this->calculator->sum([], PrecisionPolicy::default());
    }

    public function test_count_with_empty_array_throws(): void
    {
        $this->expectException(InsufficientDataException::class);

        $this->calculator->count([], PrecisionPolicy::default());
    }

    public function test_ratio_and_pct_change_fail_on_zero_denominator(): void
    {
        $this->expectException(DivideByZeroMetricException::class);

        $this->calculator->ratio(10, 0, PrecisionPolicy::default());
    }

    public function test_ratio_calates_correctly(): void
    {
        $result = $this->calculator->ratio(75, 100, PrecisionPolicy::default());

        $this->assertSame(0.75, $result);
    }

    public function test_delta_calculates_correctly(): void
    {
        $result = $this->calculator->delta(120, 100, PrecisionPolicy::default());

        $this->assertSame(20.0, $result);
    }

    public function test_absolute_delta_calculates_correctly(): void
    {
        $result = $this->calculator->absoluteDelta(100, 120, PrecisionPolicy::default());

        $this->assertSame(20.0, $result);
    }

    public function test_pct_change_calculates_correctly(): void
    {
        $result = $this->calculator->pctChange(120, 100, PrecisionPolicy::default());

        $this->assertSame(0.2, $result);
    }

    public function test_pct_change_fails_on_zero_previous(): void
    {
        $this->expectException(DivideByZeroMetricException::class);

        $this->calculator->pctChange(120, 0, PrecisionPolicy::default());
    }

    public function test_weighted_score_rejects_mismatched_values_and_weights(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Weighted calculation requires the same number of values and weights.');

        $this->calculator->weightedScore([80, 90], [0.5], PrecisionPolicy::default());
    }

    public function test_weighted_avg_calculates_correctly(): void
    {
        $result = $this->calculator->weightedAvg([80, 90, 70], [0.5, 0.3, 0.2], PrecisionPolicy::default());

        $this->assertSame(81.0, $result);
    }

    public function test_weighted_score_calculates_correctly(): void
    {
        $result = $this->calculator->weightedScore([80, 90, 70], [0.5, 0.3, 0.2], PrecisionPolicy::default());

        $this->assertSame(81.0, $result);
    }

    public function test_weighted_avg_rejects_mismatched_arrays(): void
    {
        $this->expectException(FormulaValidationException::class);

        $this->calculator->weightedAvg([80, 90], [0.5], PrecisionPolicy::default());
    }

    public function test_weighted_avg_rejects_zero_total_weight(): void
    {
        $this->expectException(DivideByZeroMetricException::class);

        $this->calculator->weightedAvg([80, 90], [1, -1], PrecisionPolicy::default());
    }

    public function test_weighted_avg_accepts_negative_values_when_weights_are_valid(): void
    {
        $result = $this->calculator->weightedAvg([-10, 20], [0.25, 0.75], PrecisionPolicy::default());

        $this->assertSame(12.5, $result);
    }
}
