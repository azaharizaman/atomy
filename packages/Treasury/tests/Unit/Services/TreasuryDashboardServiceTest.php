<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\IntercompanyLoanQueryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Entities\LiquidityPool;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Services\TreasuryDashboardService;
use Nexus\Treasury\Services\TreasuryPositionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TreasuryDashboardServiceTest extends TestCase
{
    private TreasuryDashboardService $service;
    private MockObject $liquidityPoolQuery;
    private MockObject $investmentQuery;
    private MockObject $loanQuery;
    private MockObject $approvalQuery;
    private TreasuryPositionService $positionService;

    protected function setUp(): void
    {
        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->investmentQuery = $this->createMock(InvestmentQueryInterface::class);
        $this->loanQuery = $this->createMock(IntercompanyLoanQueryInterface::class);
        $this->approvalQuery = $this->createMock(TreasuryApprovalQueryInterface::class);
        
        $policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        
        $this->positionService = new TreasuryPositionService(
            $policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            null,
            null,
            new NullLogger()
        );

        $this->service = new TreasuryDashboardService(
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            $this->loanQuery,
            $this->approvalQuery,
            $policyQuery,
            $this->positionService,
            null,
            null,
            null,
            new NullLogger()
        );
    }

    public function test_get_dashboard_returns_dashboard_metrics(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $this->approvalQuery
            ->method('countPendingByTenantId')
            ->willReturn(3);

        $this->investmentQuery
            ->method('countActiveByTenantId')
            ->willReturn(5);

        $this->loanQuery
            ->method('countActiveByTenantId')
            ->willReturn(2);

        $result = $this->service->getDashboard('tenant-001');

        $this->assertInstanceOf(\Nexus\Treasury\ValueObjects\DashboardMetrics::class, $result);
        $this->assertEquals(3, $result->pendingApprovalsCount);
        $this->assertEquals(5, $result->activeInvestmentsCount);
    }

    public function test_get_dashboard_with_custom_date(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $this->approvalQuery
            ->method('countPendingByTenantId')
            ->willReturn(0);

        $this->investmentQuery
            ->method('countActiveByTenantId')
            ->willReturn(0);

        $this->loanQuery
            ->method('countActiveByTenantId')
            ->willReturn(0);

        $asOfDate = new DateTimeImmutable('2026-01-15');
        $result = $this->service->getDashboard('tenant-001', $asOfDate);

        $this->assertInstanceOf(\Nexus\Treasury\ValueObjects\DashboardMetrics::class, $result);
    }

    public function test_get_alerts_with_no_issues(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(100000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $this->approvalQuery
            ->method('countPendingByTenantId')
            ->willReturn(2);

        $result = $this->service->getAlerts('tenant-001');

        $this->assertIsArray($result);
    }

    public function test_get_kpi_summary_returns_kpi_array(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $result = $this->service->getKpiSummary('tenant-001');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('days_cash_on_hand', $result);
        $this->assertArrayHasKey('cash_conversion_cycle', $result);
        $this->assertArrayHasKey('available_cash', $result);
    }

    public function test_get_cash_position_summary_returns_summary_array(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $result = $this->service->getCashPositionSummary('tenant-001');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_cash', $result);
        $this->assertArrayHasKey('available_cash', $result);
    }

    public function test_get_alerts_with_critical_cash_level(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(5000, 'USD'),
            availableBalance: Money::of(5000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $payableProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface::class);
        $payableProvider->method('getTotalPayables')->willReturn(10000.0);
        $payableProvider->method('getAveragePaymentPeriod')->willReturn(1);

        $policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $positionService = new TreasuryPositionService(
            $policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            $payableProvider,
            null,
            new NullLogger()
        );

        $service = new TreasuryDashboardService(
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            $this->loanQuery,
            $this->approvalQuery,
            $policyQuery,
            $positionService,
            null,
            null,
            null,
            new NullLogger()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $this->approvalQuery
            ->method('countPendingByTenantId')
            ->willReturn(2);

        $result = $service->getAlerts('tenant-001');

        $this->assertIsArray($result);
        $criticalAlerts = array_filter($result, fn($a) => $a['severity'] === 'critical');
        $this->assertGreaterThan(0, count($criticalAlerts));
    }

    public function test_get_alerts_with_many_pending_approvals(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(100000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $this->approvalQuery
            ->method('countPendingByTenantId')
            ->willReturn(15);

        $result = $this->service->getAlerts('tenant-001');

        $this->assertIsArray($result);
        $approvalAlerts = array_filter($result, fn($a) => $a['type'] === 'approvals');
        $this->assertGreaterThan(0, count($approvalAlerts));
    }

    public function test_get_dashboard_with_data_providers(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $payableProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface::class);
        $payableProvider->method('getTotalPayables')->willReturn(30000.0);
        $payableProvider->method('getAveragePaymentPeriod')->willReturn(30);
        $payableProvider->method('getDaysPayableOutstanding')->willReturn(45.0);

        $receivableProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface::class);
        $receivableProvider->method('getTotalReceivables')->willReturn(45000.0);
        $receivableProvider->method('getAverageCollectionPeriod')->willReturn(30);
        $receivableProvider->method('getDaysSalesOutstanding')->willReturn(35.0);

        $inventoryProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\InventoryDataProviderInterface::class);
        $inventoryProvider->method('getDaysInventoryOutstanding')->willReturn(25.0);

        $policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $positionService = new TreasuryPositionService(
            $policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            $payableProvider,
            $receivableProvider,
            new NullLogger()
        );

        $service = new TreasuryDashboardService(
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            $this->loanQuery,
            $this->approvalQuery,
            $policyQuery,
            $positionService,
            $payableProvider,
            $receivableProvider,
            $inventoryProvider,
            new NullLogger()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $this->approvalQuery
            ->method('countPendingByTenantId')
            ->willReturn(0);

        $this->investmentQuery
            ->method('countActiveByTenantId')
            ->willReturn(0);

        $this->loanQuery
            ->method('countActiveByTenantId')
            ->willReturn(0);

        $result = $service->getDashboard('tenant-001');

        $this->assertInstanceOf(\Nexus\Treasury\ValueObjects\DashboardMetrics::class, $result);
    }

    public function test_get_kpi_summary_with_warning_status(): void
    {
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: 'Test',
            currency: 'USD',
            totalBalance: Money::of(20000, 'USD'),
            availableBalance: Money::of(20000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['bank-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $payableProvider = $this->createMock(\Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface::class);
        $payableProvider->method('getTotalPayables')->willReturn(1000.0);
        $payableProvider->method('getAveragePaymentPeriod')->willReturn(30);

        $policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);
        $positionService = new TreasuryPositionService(
            $policyQuery,
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            null,
            $payableProvider,
            null,
            new NullLogger()
        );

        $service = new TreasuryDashboardService(
            $this->liquidityPoolQuery,
            $this->investmentQuery,
            $this->loanQuery,
            $this->approvalQuery,
            $policyQuery,
            $positionService,
            $payableProvider,
            null,
            null,
            new NullLogger()
        );

        $this->liquidityPoolQuery
            ->method('findActiveByTenantId')
            ->willReturn([$pool]);

        $this->investmentQuery
            ->method('findActiveByTenantId')
            ->willReturn([]);

        $result = $service->getKpiSummary('tenant-001');

        $this->assertIsArray($result);
        $this->assertEquals('healthy', $result['days_cash_on_hand']['status']);
    }
}
