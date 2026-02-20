<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\ValueObjects;

use Nexus\Treasury\ValueObjects\TreasuryKPIs;
use PHPUnit\Framework\TestCase;

final class TreasuryKPIsTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(30.0, $kpis->daysCashOnHand);
        $this->assertEquals(15.0, $kpis->cashConversionCycle);
        $this->assertNull($kpis->forecastAccuracy);
    }

    public function test_creates_with_forecast_accuracy(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0,
            forecastAccuracy: 85.0
        );

        $this->assertEquals(85.0, $kpis->forecastAccuracy);
    }

    public function test_from_array_creates_kpis(): void
    {
        $kpis = TreasuryKPIs::fromArray([
            'days_cash_on_hand' => 30.0,
            'cash_conversion_cycle' => 15.0,
            'days_sales_outstanding' => 30.0,
            'days_payable_outstanding' => 45.0,
            'days_inventory_outstanding' => 30.0,
            'quick_ratio' => 1.5,
            'current_ratio' => 2.0,
            'working_capital_ratio' => 2.0,
            'liquidity_score' => 75.0,
            'forecast_accuracy' => 85.0,
        ]);

        $this->assertEquals(30.0, $kpis->daysCashOnHand);
        $this->assertEquals(85.0, $kpis->forecastAccuracy);
    }

    public function test_to_array_returns_array(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $array = $kpis->toArray();

        $this->assertEquals(30.0, $array['daysCashOnHand']);
        $this->assertEquals(15.0, $array['cashConversionCycle']);
    }

    public function test_has_negative_cycle_returns_true_when_negative(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: -5.0,
            daysSalesOutstanding: 20.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 20.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertTrue($kpis->hasNegativeCycle());
    }

    public function test_has_negative_cycle_returns_false_when_positive(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertFalse($kpis->hasNegativeCycle());
    }

    public function test_has_healthy_liquidity_returns_true_when_healthy(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertTrue($kpis->hasHealthyLiquidity());
    }

    public function test_has_healthy_liquidity_returns_false_when_unhealthy(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 0.5,
            currentRatio: 1.0,
            workingCapitalRatio: 1.0,
            liquidityScore: 50.0
        );

        $this->assertFalse($kpis->hasHealthyLiquidity());
    }

    public function test_get_overall_health_score_calculates_score(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $score = $kpis->getOverallHealthScore();

        $this->assertGreaterThan(0, $score);
        $this->assertLessThan(100, $score);
    }

    public function test_get_id_returns_string(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertStringStartsWith('TRE-KPI-', $kpis->getId());
    }

    public function test_get_tenant_id_returns_tenant(): void
    {
        $kpis = new TreasuryKPIs(
            tenantId: 'tenant',
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals('tenant', $kpis->getTenantId());
    }

    public function test_get_calculation_date_returns_date_time(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $kpis->getCalculationDate());
    }

    public function test_get_days_cash_on_hand_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 45.5,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(45.5, $kpis->getDaysCashOnHand());
    }

    public function test_get_cash_conversion_cycle_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 25.5,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(25.5, $kpis->getCashConversionCycle());
    }

    public function test_get_days_sales_outstanding_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 35.5,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(35.5, $kpis->getDaysSalesOutstanding());
    }

    public function test_get_days_payable_outstanding_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 48.5,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(48.5, $kpis->getDaysPayableOutstanding());
    }

    public function test_get_days_inventory_outstanding_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 22.5,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(22.5, $kpis->getDaysInventoryOutstanding());
    }

    public function test_get_quick_ratio_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.75,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(1.75, $kpis->getQuickRatio());
    }

    public function test_get_current_ratio_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.25,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals(2.25, $kpis->getCurrentRatio());
    }

    public function test_get_working_capital_ratio_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.75,
            liquidityScore: 75.0
        );

        $this->assertEquals(2.75, $kpis->getWorkingCapitalRatio());
    }

    public function test_get_liquidity_score_returns_value(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 82.5
        );

        $this->assertEquals(82.5, $kpis->getLiquidityScore());
    }

    public function test_get_forecast_accuracy_returns_null_when_not_set(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertNull($kpis->getForecastAccuracy());
    }

    public function test_get_forecast_accuracy_returns_value_when_set(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0,
            forecastAccuracy: 88.5
        );

        $this->assertEquals(88.5, $kpis->getForecastAccuracy());
    }

    public function test_get_currency_returns_usd(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertEquals('USD', $kpis->getCurrency());
    }

    public function test_get_created_at_returns_date_time(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $kpis->getCreatedAt());
    }

    public function test_get_updated_at_returns_date_time(): void
    {
        $kpis = new TreasuryKPIs(
            daysCashOnHand: 30.0,
            cashConversionCycle: 15.0,
            daysSalesOutstanding: 30.0,
            daysPayableOutstanding: 45.0,
            daysInventoryOutstanding: 30.0,
            quickRatio: 1.5,
            currentRatio: 2.0,
            workingCapitalRatio: 2.0,
            liquidityScore: 75.0
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $kpis->getUpdatedAt());
    }
}
