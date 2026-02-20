<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\ValueObjects\DashboardMetrics;
use PHPUnit\Framework\TestCase;

final class DashboardMetricsTest extends TestCase
{
    public function test_from_array_creates_metrics(): void
    {
        $data = [
            'totalCashPosition' => 100000,
            'availableCashBalance' => 80000,
            'investedCashBalance' => 15000,
            'reservedCashBalance' => 5000,
            'projectedCashFlowToday' => 5000,
            'projectedCashFlowWeek' => 25000,
            'projectedCashFlowMonth' => 100000,
            'daysCashOnHand' => 45.5,
            'cashConversionCycle' => 25.0,
            'pendingApprovalsCount' => 3,
            'activeInvestmentsCount' => 5,
            'activeIntercompanyLoansCount' => 2,
            'alerts' => [],
            'kpiSummary' => [],
        ];

        $result = DashboardMetrics::fromArray($data);

        $this->assertEquals(100000, $result->totalCashPosition->getAmount());
        $this->assertEquals(45.5, $result->daysCashOnHand);
    }

    public function test_to_array_returns_all_properties(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2,
            alerts: [['type' => 'test', 'severity' => 'warning']],
            kpiSummary: ['days_cash_on_hand' => ['value' => 45]],
            calculatedAt: new DateTimeImmutable('2026-01-15')
        );

        $result = $metrics->toArray();

        $this->assertArrayHasKey('totalCashPosition', $result);
        $this->assertArrayHasKey('daysCashOnHand', $result);
        $this->assertArrayHasKey('alerts', $result);
    }

    public function test_get_currency_returns_currency(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'EUR'),
            availableCashBalance: Money::of(80000, 'EUR'),
            investedCashBalance: Money::of(15000, 'EUR'),
            reservedCashBalance: Money::of(5000, 'EUR'),
            projectedCashFlowToday: Money::of(5000, 'EUR'),
            projectedCashFlowWeek: Money::of(25000, 'EUR'),
            projectedCashFlowMonth: Money::of(100000, 'EUR'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals('EUR', $metrics->getCurrency());
    }

    public function test_has_alerts_returns_true_when_alerts_exist(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2,
            alerts: [['type' => 'low_cash', 'severity' => 'critical']]
        );

        $this->assertTrue($metrics->hasAlerts());
    }

    public function test_has_alerts_returns_false_when_no_alerts(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertFalse($metrics->hasAlerts());
    }

    public function test_get_critical_alerts_filters_critical_only(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2,
            alerts: [
                ['type' => 'low_cash', 'severity' => 'critical'],
                ['type' => 'warning', 'severity' => 'warning'],
                ['type' => 'urgent', 'severity' => 'critical'],
            ]
        );

        $critical = $metrics->getCriticalAlerts();

        $this->assertCount(2, $critical);
    }

    public function test_get_id_returns_string(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertStringStartsWith('TRE-DASH-', $metrics->getId());
    }

    public function test_get_dashboard_date_returns_calculated_at(): void
    {
        $date = new DateTimeImmutable('2026-01-15');
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2,
            calculatedAt: $date
        );

        $this->assertEquals($date, $metrics->getDashboardDate());
    }

    public function test_get_total_cash_position_returns_money(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(100000, $metrics->getTotalCashPosition()->getAmount());
    }

    public function test_get_available_cash_balance_returns_money(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(80000, $metrics->getAvailableCashBalance()->getAmount());
    }

    public function test_get_projected_cash_flow_methods(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(5000, $metrics->getProjectedCashFlowToday()->getAmount());
        $this->assertEquals(25000, $metrics->getProjectedCashFlowWeek()->getAmount());
        $this->assertEquals(100000, $metrics->getProjectedCashFlowMonth()->getAmount());
    }

    public function test_get_days_cash_on_hand_returns_float(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.5,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(45.5, $metrics->getDaysCashOnHand());
    }

    public function test_get_cash_conversion_cycle_returns_float(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.5,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(25.5, $metrics->getCashConversionCycle());
    }

    public function test_get_pending_approvals_count_returns_int(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 7,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(7, $metrics->getPendingApprovalsCount());
    }

    public function test_get_active_investments_count_returns_int(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 10,
            activeIntercompanyLoansCount: 2
        );

        $this->assertEquals(10, $metrics->getActiveInvestmentsCount());
    }

    public function test_get_active_intercompany_loans_count_returns_int(): void
    {
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 4
        );

        $this->assertEquals(4, $metrics->getActiveIntercompanyLoansCount());
    }

    public function test_get_alerts_returns_array(): void
    {
        $alerts = [['type' => 'test', 'severity' => 'warning']];
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2,
            alerts: $alerts
        );

        $this->assertEquals($alerts, $metrics->getAlerts());
    }

    public function test_get_kpi_summary_returns_array(): void
    {
        $kpiSummary = ['days_cash_on_hand' => ['value' => 45]];
        $metrics = new DashboardMetrics(
            totalCashPosition: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            investedCashBalance: Money::of(15000, 'USD'),
            reservedCashBalance: Money::of(5000, 'USD'),
            projectedCashFlowToday: Money::of(5000, 'USD'),
            projectedCashFlowWeek: Money::of(25000, 'USD'),
            projectedCashFlowMonth: Money::of(100000, 'USD'),
            daysCashOnHand: 45.0,
            cashConversionCycle: 25.0,
            pendingApprovalsCount: 3,
            activeInvestmentsCount: 5,
            activeIntercompanyLoansCount: 2,
            kpiSummary: $kpiSummary
        );

        $this->assertEquals($kpiSummary, $metrics->getKpiSummary());
    }
}
