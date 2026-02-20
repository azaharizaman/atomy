<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\CashManagementProviderInterface;
use Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Entities\Investment;
use Nexus\Treasury\Entities\LiquidityPool;
use Nexus\Treasury\Entities\TreasuryPolicy;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Services\TreasuryPositionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TreasuryPositionServiceTest extends TestCase
{
    private TreasuryPositionService $service;
    private MockObject $policyQuery;
    private MockObject $liquidityPoolQuery;
    private MockObject $investmentQuery;

    protected function setUp(): void
    {
        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->investmentQuery = $this->createMock(InvestmentQueryInterface::class);

        $this->liquidityPoolQuery->method('findActiveByTenantId')->willReturn([]);
        $this->investmentQuery->method('findActiveByTenantId')->willReturn([]);
        $this->policyQuery->method('findEffectiveForDate')->willReturn(null);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );
    }

    public function test_calculate_position_returns_position(): void
    {
        $result = $this->service->calculatePosition('tenant-001');

        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_calculate_position_with_entity_id(): void
    {
        $result = $this->service->calculatePosition('tenant-001', 'entity-001');

        $this->assertEquals('entity-001', $result->getEntityId());
    }

    public function test_calculate_position_with_date(): void
    {
        $date = new DateTimeImmutable('2024-01-15');

        $result = $this->service->calculatePosition('tenant-001', null, $date);

        $this->assertEquals($date->format('Y-m-d'), $result->getPositionDate()->format('Y-m-d'));
    }

    public function test_get_net_cash_position_returns_money(): void
    {
        $result = $this->service->getNetCashPosition('tenant-001');

        $this->assertInstanceOf(Money::class, $result);
    }

    public function test_has_sufficient_liquidity_returns_true_when_sufficient(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->liquidityPoolQuery->method('findActiveByTenantId')->willReturn([$pool]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->hasSufficientLiquidity('tenant-001', Money::of(50000, 'USD'));

        $this->assertTrue($result);
    }

    public function test_has_sufficient_liquidity_returns_false_when_insufficient(): void
    {
        $result = $this->service->hasSufficientLiquidity('tenant-001', Money::of(50000, 'USD'));

        $this->assertFalse($result);
    }

    public function test_get_liquidity_gap_returns_zero_when_sufficient(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->liquidityPoolQuery->method('findActiveByTenantId')->willReturn([$pool]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->getLiquidityGap('tenant-001', Money::of(50000, 'USD'));

        $this->assertEquals(0, $result->getAmount());
    }

    public function test_get_liquidity_gap_returns_difference_when_insufficient(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(20000, 'USD'),
            reservedBalance: Money::of(80000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->liquidityPoolQuery->method('findActiveByTenantId')->willReturn([$pool]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->getLiquidityGap('tenant-001', Money::of(50000, 'USD'));

        $this->assertEquals(30000, $result->getAmount());
    }

    public function test_get_days_cash_on_hand_returns_days(): void
    {
        $payableProvider = $this->createMock(PayableDataProviderInterface::class);
        $payableProvider->method('getTotalPayables')->willReturn(30000.0);
        $payableProvider->method('getAveragePaymentPeriod')->willReturn(30);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            $payableProvider,
            null,
            new NullLogger()
        );

        $result = $this->service->getDaysCashOnHand('tenant-001');

        $this->assertIsFloat($result);
    }

    public function test_get_days_cash_on_hand_returns_max_when_no_outflow(): void
    {
        $result = $this->service->getDaysCashOnHand('tenant-001');

        $this->assertEquals(PHP_FLOAT_MAX, $result);
    }

    public function test_compare_to_minimum_balance_returns_surplus(): void
    {
        $policy = new TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Default Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(100000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            status: TreasuryStatus::ACTIVE,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            description: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(50000, 'USD'),
            availableBalance: Money::of(50000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $this->policyQuery->method('findEffectiveForDate')->willReturn($policy);

        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->liquidityPoolQuery->method('findActiveByTenantId')->willReturn([$pool]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->compareToMinimumBalance('tenant-001');

        $this->assertTrue($result['is_compliant']);
        $this->assertEquals(40000, $result['surplus']->getAmount());
        $this->assertEquals(0, $result['shortfall']->getAmount());
    }

    public function test_compare_to_minimum_balance_returns_shortfall(): void
    {
        $policy = new TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Default Policy',
            minimumCashBalance: Money::of(50000, 'USD'),
            maximumSingleTransaction: Money::of(100000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            status: TreasuryStatus::ACTIVE,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            description: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(30000, 'USD'),
            availableBalance: Money::of(30000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $this->policyQuery->method('findEffectiveForDate')->willReturn($policy);

        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->liquidityPoolQuery->method('findActiveByTenantId')->willReturn([$pool]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->compareToMinimumBalance('tenant-001');

        $this->assertFalse($result['is_compliant']);
        $this->assertEquals(20000, $result['shortfall']->getAmount());
        $this->assertEquals(0, $result['surplus']->getAmount());
    }

    public function test_get_invested_cash_breakdown_returns_breakdown(): void
    {
        $investment = new Investment(
            id: 'TRE-INV-001',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::TREASURY_BILL,
            name: 'Treasury Bill',
            description: null,
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: new DateTimeImmutable('+60 days'),
            investmentDate: new DateTimeImmutable('-30 days'),
            status: InvestmentStatus::ACTIVE,
            maturityAmount: Money::of(105000, 'USD'),
            accruedInterest: Money::of(2000, 'USD'),
            bankAccountId: 'BANK-001',
            referenceNumber: 'REF-001',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->investmentQuery = $this->createMock(InvestmentQueryInterface::class);
        $this->investmentQuery->method('findActiveByTenantId')->willReturn([$investment]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->getInvestedCashBreakdown('tenant-001', 'USD');

        $this->assertCount(1, $result);
        $this->assertEquals('TRE-INV-001', $result[0]['investment_id']);
        $this->assertEquals(100000, $result[0]['principal']);
        $this->assertEquals(2000, $result[0]['accrued_interest']);
    }

    public function test_get_invested_cash_breakdown_filters_by_currency(): void
    {
        $investment = new Investment(
            id: 'TRE-INV-001',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::TREASURY_BILL,
            name: 'Treasury Bill',
            description: null,
            principalAmount: Money::of(100000, 'EUR'),
            interestRate: 5.0,
            maturityDate: new DateTimeImmutable('+60 days'),
            investmentDate: new DateTimeImmutable('-30 days'),
            status: InvestmentStatus::ACTIVE,
            maturityAmount: Money::of(105000, 'EUR'),
            accruedInterest: Money::of(2000, 'EUR'),
            bankAccountId: 'BANK-002',
            referenceNumber: 'REF-002',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->investmentQuery = $this->createMock(InvestmentQueryInterface::class);
        $this->investmentQuery->method('findActiveByTenantId')->willReturn([$investment]);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->getInvestedCashBreakdown('tenant-001', 'USD');

        $this->assertCount(0, $result);
    }

    public function test_calculate_position_with_cash_management_provider(): void
    {
        $cashProvider = $this->createMock(CashManagementProviderInterface::class);
        $cashProvider->method('getBankAccountIdsByTenant')->willReturn(['BANK-001', 'BANK-002']);
        $cashProvider->method('getCurrentBalance')->willReturn(50000.0);
        $cashProvider->method('getCurrency')->willReturn('USD');

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            $cashProvider,
            null,
            null,
            new NullLogger()
        );

        $result = $this->service->calculatePosition('tenant-001');

        $this->assertEquals(100000, $result->getTotalCashBalance()->getAmount());
    }

    public function test_calculate_position_with_receivable_provider(): void
    {
        $receivableProvider = $this->createMock(ReceivableDataProviderInterface::class);
        $receivableProvider->method('getTotalReceivables')->willReturn(60000.0);
        $receivableProvider->method('getAverageCollectionPeriod')->willReturn(30);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            $receivableProvider,
            new NullLogger()
        );

        $result = $this->service->calculatePosition('tenant-001');

        $this->assertEquals(60000, $result->getProjectedInflows()->getAmount());
    }

    public function test_calculate_position_with_payable_provider(): void
    {
        $payableProvider = $this->createMock(PayableDataProviderInterface::class);
        $payableProvider->method('getTotalPayables')->willReturn(45000.0);
        $payableProvider->method('getAveragePaymentPeriod')->willReturn(30);

        $this->service = new TreasuryPositionService(
            $this->policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            $payableProvider,
            null,
            new NullLogger()
        );

        $result = $this->service->calculatePosition('tenant-001');

        $this->assertEquals(45000, $result->getProjectedOutflows()->getAmount());
    }
}
