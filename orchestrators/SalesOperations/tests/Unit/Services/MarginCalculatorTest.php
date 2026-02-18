<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Services;

use Nexus\SalesOperations\Services\MarginCalculator;
use Nexus\SalesOperations\Services\MarginAnalysis;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarginCalculator::class)]
#[CoversClass(MarginAnalysis::class)]
final class MarginCalculatorTest extends TestCase
{
    private MarginCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MarginCalculator();
    }

    #[Test]
    public function analyze_calculates_gross_margin_correctly(): void
    {
        $lines = [
            ['quantity' => 10, 'unit_price' => 100, 'unit_cost' => 60, 'discount_percent' => 0],
        ];

        $result = $this->calculator->analyze($lines);

        $this->assertSame(1000.0, $result->totalRevenue);
        $this->assertSame(600.0, $result->totalCost);
        $this->assertSame(400.0, $result->grossProfit);
        $this->assertSame(40.0, $result->grossMarginPercent);
    }

    #[Test]
    public function analyze_applies_discount_correctly(): void
    {
        $lines = [
            ['quantity' => 10, 'unit_price' => 100, 'unit_cost' => 60, 'discount_percent' => 10],
        ];

        $result = $this->calculator->analyze($lines);

        $this->assertSame(900.0, $result->totalRevenue);
        $this->assertSame(600.0, $result->totalCost);
        $this->assertSame(300.0, $result->grossProfit);
    }

    #[Test]
    public function analyze_handles_multiple_lines(): void
    {
        $lines = [
            ['quantity' => 5, 'unit_price' => 100, 'unit_cost' => 50],
            ['quantity' => 3, 'unit_price' => 200, 'unit_cost' => 120],
        ];

        $result = $this->calculator->analyze($lines);

        $this->assertSame(1100.0, $result->totalRevenue);
        $this->assertSame(610.0, $result->totalCost);
        $this->assertSame(490.0, $result->grossProfit);
    }

    #[Test]
    public function analyze_handles_zero_revenue(): void
    {
        $lines = [
            ['quantity' => 0, 'unit_price' => 100, 'unit_cost' => 50],
        ];

        $result = $this->calculator->analyze($lines);

        $this->assertSame(0.0, $result->totalRevenue);
        $this->assertSame(0.0, $result->grossMarginPercent);
    }

    #[Test]
    public function analyze_estimates_cost_when_not_provided(): void
    {
        $lines = [
            ['quantity' => 10, 'unit_price' => 100],
        ];

        $result = $this->calculator->analyze($lines);

        $this->assertSame(1000.0, $result->totalRevenue);
        $this->assertSame(600.0, $result->totalCost);
    }

    #[Test]
    public function analyze_includes_landed_cost_when_requested(): void
    {
        $lines = [
            ['quantity' => 10, 'unit_price' => 100, 'unit_cost' => 50, 'landed_cost_per_unit' => 10],
        ];

        $result = $this->calculator->analyze($lines, 'weighted_average', true);

        $this->assertSame(600.0, $result->totalCost);
    }

    #[Test]
    public function margin_analysis_holds_correct_values(): void
    {
        $analysis = new MarginAnalysis(
            totalRevenue: 1000.0,
            totalCost: 600.0,
            grossProfit: 400.0,
            grossMarginPercent: 40.0,
            netMarginPercent: 35.0
        );

        $this->assertSame(1000.0, $analysis->totalRevenue);
        $this->assertSame(600.0, $analysis->totalCost);
        $this->assertSame(400.0, $analysis->grossProfit);
        $this->assertSame(40.0, $analysis->grossMarginPercent);
        $this->assertSame(35.0, $analysis->netMarginPercent);
    }
}
