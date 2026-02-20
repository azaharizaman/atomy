<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Services\TreasuryAnalyticsService;
use Nexus\Treasury\Services\TreasuryPositionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TreasuryAnalyticsServiceTest extends TestCase
{
    private TreasuryAnalyticsService $service;
    private MockObject $policyQuery;
    private TreasuryPositionService $positionService;

    protected function setUp(): void
    {
        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        
        $liquidityPoolQuery = $this->createMock(\Nexus\Treasury\Contracts\LiquidityPoolQueryInterface::class);
        $investmentQuery = $this->createMock(InvestmentQueryInterface::class);
        
        $this->positionService = new TreasuryPositionService(
            $this->policyQuery,
            $liquidityPoolQuery,
            $investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $this->service = new TreasuryAnalyticsService(
            $this->policyQuery,
            $investmentQuery,
            $this->positionService,
            null,
            null,
            new NullLogger()
        );
    }

    public function test_calculate_kpis_returns_kpis_interface(): void
    {
        $result = $this->service->calculateKPIs('tenant-001');

        $this->assertInstanceOf(\Nexus\Treasury\Contracts\TreasuryAnalyticsInterface::class, $result);
    }

    public function test_get_liquidity_score_returns_float(): void
    {
        $result = $this->service->getLiquidityScore('tenant-001');

        $this->assertIsFloat($result);
    }

    public function test_get_cash_flow_metrics_returns_array(): void
    {
        $result = $this->service->getCashFlowMetrics('tenant-001');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_cash_balance', $result);
        $this->assertArrayHasKey('available_cash', $result);
        $this->assertArrayHasKey('invested_cash', $result);
    }

    public function test_get_investment_metrics_returns_array(): void
    {
        $investmentQuery = $this->createMock(InvestmentQueryInterface::class);
        $investmentQuery->method('findActiveByTenantId')->willReturn([]);
        $investmentQuery->method('sumPrincipalByTenantId')->willReturn(0.0);
        $investmentQuery->method('countActiveByTenantId')->willReturn(0);
        $investmentQuery->method('findMaturedByTenantId')->willReturn([]);

        $service = new TreasuryAnalyticsService(
            $this->policyQuery,
            $investmentQuery,
            $this->positionService,
            null,
            null,
            new NullLogger()
        );

        $result = $service->getInvestmentMetrics('tenant-001');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_invested', $result);
        $this->assertArrayHasKey('active_investments', $result);
        $this->assertArrayHasKey('average_interest_rate', $result);
    }

    public function test_get_working_capital_metrics_returns_array(): void
    {
        $result = $this->service->getWorkingCapitalMetrics('tenant-001');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('days_sales_outstanding', $result);
        $this->assertArrayHasKey('days_payable_outstanding', $result);
        $this->assertArrayHasKey('cash_conversion_cycle', $result);
    }

    public function test_compare_to_benchmarks_returns_comparisons(): void
    {
        $benchmarks = [
            'days_cash_on_hand' => 30,
            'cash_conversion_cycle' => 30,
            'quick_ratio' => 1.0,
        ];

        $result = $this->service->compareToBenchmarks('tenant-001', $benchmarks);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('days_cash_on_hand', $result);
        $this->assertArrayHasKey('cash_conversion_cycle', $result);
    }

    public function test_calculate_kpis_includes_all_metrics(): void
    {
        $result = $this->service->calculateKPIs('tenant-001');

        $this->assertInstanceOf(\Nexus\Treasury\ValueObjects\TreasuryKPIs::class, $result);
    }
}
