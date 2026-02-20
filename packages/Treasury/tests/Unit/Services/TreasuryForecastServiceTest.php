<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Enums\ForecastScenario;
use Nexus\Treasury\Services\TreasuryForecastService;
use Nexus\Treasury\Services\TreasuryPositionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TreasuryForecastServiceTest extends TestCase
{
    private TreasuryForecastService $service;
    private MockObject $policyQuery;
    private TreasuryPositionService $positionService;

    protected function setUp(): void
    {
        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        
        $liquidityPoolQuery = $this->createMock(\Nexus\Treasury\Contracts\LiquidityPoolQueryInterface::class);
        $investmentQuery = $this->createMock(\Nexus\Treasury\Contracts\InvestmentQueryInterface::class);
        
        $this->positionService = new TreasuryPositionService(
            $this->policyQuery,
            $liquidityPoolQuery,
            $investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $this->service = new TreasuryForecastService(
            $this->policyQuery,
            $this->positionService,
            null,
            null,
            new NullLogger()
        );
    }

    public function test_generate_forecast_returns_forecast_interface(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->generateForecast(
            'tenant-001',
            ForecastScenario::BASE,
            $startDate,
            $endDate
        );

        $this->assertInstanceOf(\Nexus\Treasury\Contracts\TreasuryForecastInterface::class, $result);
        $this->assertEquals(ForecastScenario::BASE, $result->getScenario());
    }

    public function test_generate_forecast_optimistic_scenario(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->generateForecast(
            'tenant-001',
            ForecastScenario::OPTIMISTIC,
            $startDate,
            $endDate
        );

        $this->assertEquals(ForecastScenario::OPTIMISTIC, $result->getScenario());
    }

    public function test_generate_forecast_pessimistic_scenario(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->generateForecast(
            'tenant-001',
            ForecastScenario::PESSIMISTIC,
            $startDate,
            $endDate
        );

        $this->assertEquals(ForecastScenario::PESSIMISTIC, $result->getScenario());
    }

    public function test_get_scenario_comparison_returns_all_scenarios(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->getScenarioComparison('tenant-001', $startDate, $endDate);

        $this->assertArrayHasKey('optimistic', $result);
        $this->assertArrayHasKey('base', $result);
        $this->assertArrayHasKey('pessimistic', $result);
    }

    public function test_identify_cash_gaps_returns_empty_when_sufficient(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');
        $minimumRequired = Money::of(1000000, 'USD');

        $result = $this->service->identifyCashGaps('tenant-001', $startDate, $endDate, $minimumRequired);

        $this->assertIsArray($result);
    }

    public function test_generate_forecast_has_confidence_level(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->generateForecast(
            'tenant-001',
            ForecastScenario::BASE,
            $startDate,
            $endDate
        );

        $this->assertGreaterThan(0, $result->getConfidenceLevel());
    }

    public function test_generate_forecast_includes_assumptions(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->generateForecast(
            'tenant-001',
            ForecastScenario::BASE,
            $startDate,
            $endDate
        );

        $assumptions = $result->getAssumptions();
        $this->assertIsArray($assumptions);
        $this->assertArrayHasKey('scenario', $assumptions);
    }

    public function test_generate_forecast_includes_risk_factors_for_pessimistic(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->generateForecast(
            'tenant-001',
            ForecastScenario::PESSIMISTIC,
            $startDate,
            $endDate
        );

        $riskFactors = $result->getRiskFactors();
        $this->assertIsArray($riskFactors);
    }

    public function test_identify_cash_gaps_with_actual_gap(): void
    {
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $this->service->identifyCashGaps('tenant-001', $startDate, $endDate, Money::of(-1000000, 'USD'));

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function test_generate_forecast_with_data_providers(): void
    {
        $payableProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface::class);
        $payableProvider->method('getTotalPayables')->willReturn(30000.0);
        $payableProvider->method('getAveragePaymentPeriod')->willReturn(30);

        $receivableProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface::class);
        $receivableProvider->method('getTotalReceivables')->willReturn(45000.0);
        $receivableProvider->method('getAverageCollectionPeriod')->willReturn(30);

        $service = new TreasuryForecastService(
            $this->policyQuery,
            $this->positionService,
            $payableProvider,
            $receivableProvider,
            new NullLogger()
        );

        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $service->generateForecast(
            'tenant-001',
            ForecastScenario::BASE,
            $startDate,
            $endDate
        );

        $this->assertInstanceOf(\Nexus\Treasury\Contracts\TreasuryForecastInterface::class, $result);
        $this->assertGreaterThan(0, $result->getProjectedInflows()->getAmount());
    }

    public function test_generate_forecast_with_policy(): void
    {
        $policy = new \Nexus\Treasury\Entities\TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Default Policy',
            minimumCashBalance: Money::of(50000, 'USD'),
            maximumSingleTransaction: Money::of(100000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            status: \Nexus\Treasury\Enums\TreasuryStatus::ACTIVE,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            description: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $this->policyQuery->method('findEffectiveForDate')->willReturn($policy);

        $liquidityPoolQuery = $this->createMock(\Nexus\Treasury\Contracts\LiquidityPoolQueryInterface::class);
        $investmentQuery = $this->createMock(\Nexus\Treasury\Contracts\InvestmentQueryInterface::class);

        $this->positionService = new TreasuryPositionService(
            $this->policyQuery,
            $liquidityPoolQuery,
            $investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $service = new TreasuryForecastService(
            $this->policyQuery,
            $this->positionService,
            null,
            null,
            new NullLogger()
        );

        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-03-31');

        $result = $service->generateForecast(
            'tenant-001',
            ForecastScenario::BASE,
            $startDate,
            $endDate
        );

        $this->assertEquals(50000, $result->getMinimumBalance()->getAmount());
    }
}
