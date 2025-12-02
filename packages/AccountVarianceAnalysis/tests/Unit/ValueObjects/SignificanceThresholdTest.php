<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Tests\Unit\ValueObjects;

use Nexus\AccountVarianceAnalysis\ValueObjects\SignificanceThreshold;
use PHPUnit\Framework\TestCase;

final class SignificanceThresholdTest extends TestCase
{
    public function test_constructs_with_valid_data(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            15.0,
            false
        );

        $this->assertInstanceOf(SignificanceThreshold::class, $threshold);
    }

    public function test_gets_amount_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            15.0,
            false
        );

        $this->assertSame(10000.00, $threshold->getAmountThreshold());
    }

    public function test_gets_percentage_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            15.0,
            false
        );

        $this->assertSame(15.0, $threshold->getPercentageThreshold());
    }

    public function test_require_both_returns_false_by_default(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            15.0
        );

        $this->assertFalse($threshold->requireBoth());
    }

    public function test_require_both_returns_true_when_set(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            15.0,
            true
        );

        $this->assertTrue($threshold->requireBoth());
    }

    public function test_require_both_returns_false_when_explicitly_set(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            15.0,
            false
        );

        $this->assertFalse($threshold->requireBoth());
    }

    public function test_handles_zero_amount_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            0.00,
            10.0,
            false
        );

        $this->assertSame(0.00, $threshold->getAmountThreshold());
    }

    public function test_handles_zero_percentage_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            5000.00,
            0.0,
            false
        );

        $this->assertSame(0.0, $threshold->getPercentageThreshold());
    }

    public function test_handles_large_amount_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            10000000.00,
            5.0,
            false
        );

        $this->assertSame(10000000.00, $threshold->getAmountThreshold());
    }

    public function test_handles_large_percentage_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            5000.00,
            500.0,
            false
        );

        $this->assertSame(500.0, $threshold->getPercentageThreshold());
    }

    public function test_handles_small_percentage_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            0.5,
            false
        );

        $this->assertSame(0.5, $threshold->getPercentageThreshold());
    }

    public function test_handles_decimal_amount_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            12345.67,
            10.5,
            false
        );

        $this->assertSame(12345.67, $threshold->getAmountThreshold());
    }

    public function test_handles_decimal_percentage_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            12.345,
            false
        );

        $this->assertSame(12.345, $threshold->getPercentageThreshold());
    }

    public function test_handles_negative_amount_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            -5000.00,
            10.0,
            false
        );

        $this->assertSame(-5000.00, $threshold->getAmountThreshold());
    }

    public function test_handles_negative_percentage_threshold(): void
    {
        $threshold = new SignificanceThreshold(
            5000.00,
            -10.0,
            false
        );

        $this->assertSame(-10.0, $threshold->getPercentageThreshold());
    }

    public function test_common_percentage_thresholds(): void
    {
        $threshold5 = new SignificanceThreshold(0.00, 5.0, false);
        $threshold10 = new SignificanceThreshold(0.00, 10.0, false);
        $threshold15 = new SignificanceThreshold(0.00, 15.0, false);
        $threshold20 = new SignificanceThreshold(0.00, 20.0, false);

        $this->assertSame(5.0, $threshold5->getPercentageThreshold());
        $this->assertSame(10.0, $threshold10->getPercentageThreshold());
        $this->assertSame(15.0, $threshold15->getPercentageThreshold());
        $this->assertSame(20.0, $threshold20->getPercentageThreshold());
    }

    public function test_typical_amount_thresholds(): void
    {
        $threshold1k = new SignificanceThreshold(1000.00, 0.0, false);
        $threshold5k = new SignificanceThreshold(5000.00, 0.0, false);
        $threshold10k = new SignificanceThreshold(10000.00, 0.0, false);
        $threshold50k = new SignificanceThreshold(50000.00, 0.0, false);

        $this->assertSame(1000.00, $threshold1k->getAmountThreshold());
        $this->assertSame(5000.00, $threshold5k->getAmountThreshold());
        $this->assertSame(10000.00, $threshold10k->getAmountThreshold());
        $this->assertSame(50000.00, $threshold50k->getAmountThreshold());
    }

    public function test_combined_threshold_scenario(): void
    {
        $threshold = new SignificanceThreshold(
            10000.00,
            10.0,
            true
        );

        $this->assertSame(10000.00, $threshold->getAmountThreshold());
        $this->assertSame(10.0, $threshold->getPercentageThreshold());
        $this->assertTrue($threshold->requireBoth());
    }

    public function test_amount_only_scenario(): void
    {
        $threshold = new SignificanceThreshold(
            15000.00,
            0.0,
            false
        );

        $this->assertSame(15000.00, $threshold->getAmountThreshold());
        $this->assertSame(0.0, $threshold->getPercentageThreshold());
        $this->assertFalse($threshold->requireBoth());
    }

    public function test_percentage_only_scenario(): void
    {
        $threshold = new SignificanceThreshold(
            0.00,
            12.5,
            false
        );

        $this->assertSame(0.00, $threshold->getAmountThreshold());
        $this->assertSame(12.5, $threshold->getPercentageThreshold());
        $this->assertFalse($threshold->requireBoth());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(SignificanceThreshold::class);
        
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_is_final(): void
    {
        $reflection = new \ReflectionClass(SignificanceThreshold::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function test_high_precision_decimal_values(): void
    {
        $threshold = new SignificanceThreshold(
            12345.6789,
            15.123456,
            true
        );

        $this->assertSame(12345.6789, $threshold->getAmountThreshold());
        $this->assertSame(15.123456, $threshold->getPercentageThreshold());
    }
}
