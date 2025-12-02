<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Tests\Unit\ValueObjects;

use Nexus\AccountVarianceAnalysis\Enums\VarianceType;
use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceResult;
use PHPUnit\Framework\TestCase;

final class VarianceResultTest extends TestCase
{
    public function test_constructs_with_valid_data(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertInstanceOf(VarianceResult::class, $result);
    }

    public function test_gets_actual_amount(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(150000.00, $result->getActual());
    }

    public function test_gets_baseline_amount(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(100000.00, $result->getBaseline());
    }

    public function test_gets_variance_amount(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(50000.00, $result->getVarianceAmount());
    }

    public function test_gets_variance_percentage(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(50.0, $result->getVariancePercentage());
    }

    public function test_gets_variance_type(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(VarianceType::FAVORABLE, $result->getType());
    }

    public function test_is_favorable_returns_true_when_type_is_favorable(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertTrue($result->isFavorable());
    }

    public function test_is_favorable_returns_false_when_type_is_unfavorable(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::UNFAVORABLE
        );

        $this->assertFalse($result->isFavorable());
    }

    public function test_is_favorable_returns_false_when_type_is_neutral(): void
    {
        $result = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );

        $this->assertFalse($result->isFavorable());
    }

    public function test_is_unfavorable_returns_true_when_type_is_unfavorable(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::UNFAVORABLE
        );

        $this->assertTrue($result->isUnfavorable());
    }

    public function test_is_unfavorable_returns_false_when_type_is_favorable(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertFalse($result->isUnfavorable());
    }

    public function test_is_unfavorable_returns_false_when_type_is_neutral(): void
    {
        $result = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );

        $this->assertFalse($result->isUnfavorable());
    }

    public function test_handles_zero_actual_amount(): void
    {
        $result = new VarianceResult(
            0.00,
            100000.00,
            -100000.00,
            -100.0,
            VarianceType::UNFAVORABLE
        );

        $this->assertSame(0.00, $result->getActual());
        $this->assertSame(-100000.00, $result->getVarianceAmount());
    }

    public function test_handles_zero_baseline_amount(): void
    {
        $result = new VarianceResult(
            100000.00,
            0.00,
            100000.00,
            0.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(0.00, $result->getBaseline());
        $this->assertSame(100000.00, $result->getVarianceAmount());
    }

    public function test_handles_negative_actual_amount(): void
    {
        $result = new VarianceResult(
            -50000.00,
            100000.00,
            -150000.00,
            -150.0,
            VarianceType::UNFAVORABLE
        );

        $this->assertSame(-50000.00, $result->getActual());
        $this->assertSame(-150000.00, $result->getVarianceAmount());
    }

    public function test_handles_negative_baseline_amount(): void
    {
        $result = new VarianceResult(
            50000.00,
            -100000.00,
            150000.00,
            -150.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(-100000.00, $result->getBaseline());
        $this->assertSame(150000.00, $result->getVarianceAmount());
    }

    public function test_handles_zero_variance(): void
    {
        $result = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );

        $this->assertSame(0.00, $result->getVarianceAmount());
        $this->assertSame(0.0, $result->getVariancePercentage());
        $this->assertSame(VarianceType::NEUTRAL, $result->getType());
    }

    public function test_handles_positive_variance_percentage(): void
    {
        $result = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(50.0, $result->getVariancePercentage());
    }

    public function test_handles_negative_variance_percentage(): void
    {
        $result = new VarianceResult(
            50000.00,
            100000.00,
            -50000.00,
            -50.0,
            VarianceType::UNFAVORABLE
        );

        $this->assertSame(-50.0, $result->getVariancePercentage());
    }

    public function test_handles_large_positive_variance(): void
    {
        $result = new VarianceResult(
            1000000.00,
            100000.00,
            900000.00,
            900.0,
            VarianceType::FAVORABLE
        );

        $this->assertSame(900000.00, $result->getVarianceAmount());
        $this->assertSame(900.0, $result->getVariancePercentage());
    }

    public function test_handles_large_negative_variance(): void
    {
        $result = new VarianceResult(
            10000.00,
            1000000.00,
            -990000.00,
            -99.0,
            VarianceType::UNFAVORABLE
        );

        $this->assertSame(-990000.00, $result->getVarianceAmount());
        $this->assertSame(-99.0, $result->getVariancePercentage());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(VarianceResult::class);
        
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_is_final(): void
    {
        $reflection = new \ReflectionClass(VarianceResult::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function test_handles_decimal_precision(): void
    {
        $result = new VarianceResult(
            123456.789,
            123456.123,
            0.666,
            0.00053963,
            VarianceType::FAVORABLE
        );

        $this->assertSame(123456.789, $result->getActual());
        $this->assertSame(123456.123, $result->getBaseline());
        $this->assertSame(0.666, $result->getVarianceAmount());
        $this->assertSame(0.00053963, $result->getVariancePercentage());
    }
}
