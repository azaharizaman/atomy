<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\DataProviders;

use ArrayIterator;
use Nexus\FinanceOperations\Contracts\BudgetQueryInterface;
use Nexus\FinanceOperations\Contracts\CostAccountingManagerQueryInterface;
use Nexus\FinanceOperations\Contracts\LedgerQueryInterface;
use Nexus\FinanceOperations\DataProviders\CostAccountingDataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CostAccountingDataProviderTest extends TestCase
{
    private CostAccountingManagerQueryInterface $costManager;
    private LedgerQueryInterface $ledgerQuery;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->costManager = $this->createMock(CostAccountingManagerQueryInterface::class);
        $this->ledgerQuery = $this->createMock(LedgerQueryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetCostCenterSummaryIncludesVarianceWhenBudgetAvailable(): void
    {
        $costCenter = new class {
            public function getName(): string { return 'Operations'; }
            public function getCode(): string { return 'OPS'; }
            public function isActive(): bool { return true; }
            public function getResponsiblePerson(): string { return 'user-1'; }
            public function getDepartment(): string { return 'Finance'; }
        };

        $actualBalance = new class {
            public function getBalance(): string { return '80.00'; }
            public function getCurrency(): string { return 'USD'; }
        };

        $budget = new class {
            public function getAmount(): string { return '100.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getPeriodId(): string { return '2026-04'; }
        };

        $budgetQuery = $this->createMock(BudgetQueryInterface::class);
        $budgetQuery
            ->expects($this->once())
            ->method('getCostCenterBudget')
            ->with('tenant-1', 'cc-1', '2026-04')
            ->willReturn($budget);

        $this->costManager
            ->expects($this->once())
            ->method('getCostCenter')
            ->with('tenant-1', 'cc-1')
            ->willReturn($costCenter);

        $this->ledgerQuery
            ->expects($this->once())
            ->method('getCostCenterBalance')
            ->with('tenant-1', 'cc-1', '2026-04')
            ->willReturn($actualBalance);

        $provider = new CostAccountingDataProvider(
            $this->costManager,
            $this->ledgerQuery,
            $budgetQuery,
            $this->logger
        );

        $result = $provider->getCostCenterSummary('tenant-1', 'cc-1', '2026-04');

        $this->assertSame('cc-1', $result['cost_center_id']);
        $this->assertSame('20.00', $result['variance']);
        $this->assertSame(20.0, $result['variance_percent']);
        $this->assertSame('USD', $result['currency']);
    }

    public function testGetAllocatedCostsSupportsTraversableAllocations(): void
    {
        $this->costManager
            ->expects($this->once())
            ->method('getPeriodAllocations')
            ->with('tenant-1', '2026-04')
            ->willReturn(new ArrayIterator([
                new class {
                    public function getCostCenterId(): string { return 'cc-1'; }
                    public function getCostCenterName(): string { return 'Operations'; }
                    public function getSourcePool(): string { return 'pool-1'; }
                    public function getSourcePoolName(): string { return 'Shared Services'; }
                    public function getAmount(): string { return '25.00'; }
                    public function getMethod(): string { return 'headcount'; }
                    public function getAllocatedAt(): \DateTimeImmutable { return new \DateTimeImmutable('2026-04-10 10:00:00'); }
                },
                new class {
                    public function getCostCenterId(): string { return 'cc-1'; }
                    public function getCostCenterName(): string { return 'Operations'; }
                    public function getSourcePool(): string { return 'pool-2'; }
                    public function getSourcePoolName(): string { return 'Facilities'; }
                    public function getAmount(): string { return '15.00'; }
                    public function getMethod(): string { return 'floor_space'; }
                    public function getAllocatedAt(): \DateTimeImmutable { return new \DateTimeImmutable('2026-04-11 10:00:00'); }
                },
            ]));

        $provider = new CostAccountingDataProvider(
            $this->costManager,
            $this->ledgerQuery,
            null,
            $this->logger
        );

        $result = $provider->getAllocatedCosts('tenant-1', '2026-04');

        $this->assertSame('40.00', $result['total_allocated']);
        $this->assertSame(2, $result['allocation_count']);
        $this->assertSame(1, $result['cost_center_count']);
    }

    public function testGetCostCenterSummaryReturnsNullVarianceWhenBudgetUnavailable(): void
    {
        $costCenter = new class {
            public function getName(): string { return 'Operations'; }
            public function getCode(): string { return 'OPS'; }
            public function isActive(): bool { return true; }
            public function getResponsiblePerson(): string { return 'user-1'; }
            public function getDepartment(): string { return 'Finance'; }
        };

        $actualBalance = new class {
            public function getBalance(): string { return '80.00'; }
            public function getCurrency(): string { return 'USD'; }
        };

        $this->costManager
            ->expects($this->once())
            ->method('getCostCenter')
            ->with('tenant-1', 'cc-1')
            ->willReturn($costCenter);

        $this->ledgerQuery
            ->expects($this->once())
            ->method('getCostCenterBalance')
            ->with('tenant-1', 'cc-1', '2026-05')
            ->willReturn($actualBalance);

        $provider = new CostAccountingDataProvider(
            $this->costManager,
            $this->ledgerQuery,
            null,
            $this->logger
        );

        $result = $provider->getCostCenterSummary('tenant-1', 'cc-1', '2026-05');

        $this->assertNull($result['budget']);
        $this->assertNull($result['variance']);
        $this->assertNull($result['variance_percent']);
    }
}
