<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Services;

use Nexus\SalesOperations\Services\CommissionCalculator;
use Nexus\SalesOperations\Services\CommissionInput;
use Nexus\SalesOperations\Services\CommissionResult;
use Nexus\SalesOperations\Services\CommissionSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommissionCalculator::class)]
#[CoversClass(CommissionInput::class)]
#[CoversClass(CommissionResult::class)]
#[CoversClass(CommissionSummary::class)]
final class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CommissionCalculator(
            defaultBasis: 'gross_profit',
            defaultRate: 10.0,
            tier2Threshold: 50000.0,
            tier2Rate: 12.0,
            tier3Threshold: 100000.0,
            tier3Rate: 15.0
        );
    }

    #[Test]
    public function calculate_uses_default_rate(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR'
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame('sp-1', $result->salespersonId);
        $this->assertSame(10.0, $result->rate);
        $this->assertSame(4000.0, $result->commissionableAmount);
        $this->assertSame(400.0, $result->commissionAmount);
    }

    #[Test]
    public function calculate_uses_revenue_basis(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR',
            basis: 'revenue'
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame('revenue', $result->basis);
        $this->assertSame(10000.0, $result->commissionableAmount);
        $this->assertSame(1000.0, $result->commissionAmount);
    }

    #[Test]
    public function calculate_applies_tier2_rate(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR',
            ytdRevenue: 60000.0
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(12.0, $result->rate);
    }

    #[Test]
    public function calculate_applies_tier3_rate(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR',
            ytdRevenue: 120000.0
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(15.0, $result->rate);
    }

    #[Test]
    public function calculate_respects_custom_rate(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR',
            customRate: 8.0
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(8.0, $result->rate);
    }

    #[Test]
    public function calculate_handles_zero_gross_profit(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 10000.0,
            currencyCode: 'MYR'
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(0.0, $result->commissionableAmount);
        $this->assertSame(0.0, $result->commissionAmount);
    }

    #[Test]
    public function calculate_with_override(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR'
        );

        $result = $this->calculator->calculateWithOverride($input, 20.0, 'Manager approved');

        $this->assertSame(20.0, $result->rate);
        $this->assertTrue($result->isOverride);
        $this->assertSame('Manager approved', $result->overrideReason);
    }

    #[Test]
    public function calculate_team_split(): void
    {
        $input = new CommissionInput(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            currencyCode: 'MYR'
        );

        $splits = [
            ['salespersonId' => 'sp-1', 'percent' => 60],
            ['salespersonId' => 'sp-2', 'percent' => 40],
        ];

        $results = $this->calculator->calculateTeamSplit($input, $splits);

        $this->assertCount(2, $results);
        $this->assertSame('sp-1', $results[0]->salespersonId);
        $this->assertSame(60.0, $results[0]->splitPercent);
        $this->assertSame('sp-2', $results[1]->salespersonId);
        $this->assertSame(40.0, $results[1]->splitPercent);
    }

    #[Test]
    public function calculate_for_period(): void
    {
        $payments = [
            ['order_id' => 'order-1', 'payment_id' => 'pay-1', 'revenue_amount' => 10000.0, 'cost_amount' => 6000.0],
            ['order_id' => 'order-2', 'payment_id' => 'pay-2', 'revenue_amount' => 5000.0, 'cost_amount' => 3000.0],
        ];

        $summary = $this->calculator->calculateForPeriod('sp-1', $payments);

        $this->assertSame('sp-1', $summary->salespersonId);
        $this->assertSame(15000.0, $summary->totalRevenue);
        $this->assertSame(9000.0, $summary->totalCost);
        $this->assertSame(6000.0, $summary->totalGrossProfit);
        $this->assertSame(2, $summary->transactionCount);
    }

    #[Test]
    public function commission_result_effective_rate(): void
    {
        $result = new CommissionResult(
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            basis: 'gross_profit',
            rate: 10.0,
            revenueAmount: 10000.0,
            costAmount: 6000.0,
            grossProfit: 4000.0,
            commissionableAmount: 4000.0,
            commissionAmount: 400.0,
            currencyCode: 'MYR'
        );

        $this->assertSame(4.0, $result->effectiveRate());
    }

    #[Test]
    public function commission_summary_calculations(): void
    {
        $summary = new CommissionSummary(
            salespersonId: 'sp-1',
            totalRevenue: 100000.0,
            totalCost: 60000.0,
            totalGrossProfit: 40000.0,
            totalCommission: 4000.0,
            transactionCount: 10,
            currencyCode: 'MYR'
        );

        $this->assertSame(400.0, $summary->averageCommission());
        $this->assertSame(40.0, $summary->grossMarginPercent());
        $this->assertSame(4.0, $summary->effectiveCommissionRate());
    }
}
