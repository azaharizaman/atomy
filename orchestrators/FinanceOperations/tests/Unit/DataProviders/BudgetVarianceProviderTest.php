<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\DataProviders;

use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\DataProviders\BudgetVarianceProvider;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BudgetVarianceProvider.
 *
 * Tests cover:
 * - Get budget data
 * - Get actual data
 * - Variance analysis
 * - Error handling
 *
 * @since 1.0.0
 */
final class BudgetVarianceProviderTest extends TestCase
{
    private object $budgetQueryMock;
    private object $glQueryMock;
    private ?object $costQueryMock;
    private object $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->budgetQueryMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getBudgetsByPeriod'])
            ->getMock();

        $this->glQueryMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getActualsByPeriod'])
            ->getMock();

        $this->costQueryMock = null;

        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    // =========================================================================
    // Test Suite: getBudgetData()
    // =========================================================================

    /**
     * Test getBudgetData returns formatted budget data.
     */
    public function testGetBudgetDataReturnsFormattedData(): void
    {
        // Create mock budget objects
        $budgetMock = new class {
            public function getId(): string { return 'budget-001'; }
            public function getName(): string { return 'Q1 Budget'; }
            public function getCostCenterId(): string { return 'cc-001'; }
            public function getCostCenterName(): string { return 'Operations'; }
            public function getAccountCode(): string { return '5000'; }
            public function getAccountName(): string { return 'Salaries'; }
            public function getAmount(): string { return '50000.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getVersion(): string { return 'v1'; }
            public function getVersionName(): string { return 'Original'; }
            public function isOriginal(): bool { return true; }
        };

        $this->budgetQueryMock
            ->expects($this->once())
            ->method('getBudgetsByPeriod')
            ->with('tenant-001', '2026-01', null)
            ->willReturn([$budgetMock]);

        $this->loggerMock
            ->expects($this->once())
            ->method('debug')
            ->with('Fetching budget data', $this->anything());

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $result = $provider->getBudgetData('tenant-001', '2026-01');

        $this->assertIsArray($result);
        $this->assertEquals('2026-01', $result['period_id']);
        $this->assertCount(1, $result['budgets']);
        $this->assertEquals('budget-001', $result['budgets'][0]['budget_id']);
        $this->assertEquals('50000.00', $result['budgets'][0]['budgeted_amount']);
    }

    /**
     * Test getBudgetData throws exception and logs error.
     */
    public function testGetBudgetDataThrowsAndLogsError(): void
    {
        $this->budgetQueryMock
            ->expects($this->once())
            ->method('getBudgetsByPeriod')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to fetch budget data', $this->anything());

        $this->expectException(\RuntimeException::class);

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $provider->getBudgetData('tenant-001', '2026-01');
    }

    // =========================================================================
    // Test Suite: getActualData()
    // =========================================================================

    /**
     * Test getActualData returns formatted actual data.
     */
    public function testGetActualDataReturnsFormattedData(): void
    {
        // Create mock actual objects
        $actualMock = new class {
            public function getAccountCode(): string { return '5000'; }
            public function getAccountName(): string { return 'Salaries'; }
            public function getCostCenterId(): string { return 'cc-001'; }
            public function getCostCenterName(): string { return 'Operations'; }
            public function getBalance(): string { return '45000.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getDebitTotal(): string { return '45000.00'; }
            public function getCreditTotal(): string { return '0'; }
        };

        $this->glQueryMock
            ->expects($this->once())
            ->method('getActualsByPeriod')
            ->with('tenant-001', '2026-01')
            ->willReturn([$actualMock]);

        $this->loggerMock
            ->expects($this->once())
            ->method('debug')
            ->with('Fetching actual data', $this->anything());

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $result = $provider->getActualData('tenant-001', '2026-01');

        $this->assertIsArray($result);
        $this->assertEquals('2026-01', $result['period_id']);
        $this->assertCount(1, $result['actuals']);
        $this->assertEquals('45000.00', $result['actuals'][0]['actual_amount']);
    }

    /**
     * Test getActualData throws exception and logs error.
     */
    public function testGetActualDataThrowsAndLogsError(): void
    {
        $this->glQueryMock
            ->expects($this->once())
            ->method('getActualsByPeriod')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to fetch actual data', $this->anything());

        $this->expectException(\RuntimeException::class);

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $provider->getActualData('tenant-001', '2026-01');
    }

    // =========================================================================
    // Test Suite: getVarianceAnalysis()
    // =========================================================================

    /**
     * Test getVarianceAnalysis performs variance calculation.
     */
    public function testGetVarianceAnalysisCalculatesVariance(): void
    {
        // Create mock budget
        $budgetMock = new class {
            public function getId(): string { return 'budget-001'; }
            public function getName(): string { return 'Q1 Budget'; }
            public function getCostCenterId(): string { return 'cc-001'; }
            public function getCostCenterName(): string { return 'Operations'; }
            public function getAccountCode(): string { return '5000'; } // Expense account
            public function getAccountName(): string { return 'Salaries'; }
            public function getAmount(): string { return '50000.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getVersion(): string { return 'v1'; }
            public function getVersionName(): string { return 'Original'; }
            public function isOriginal(): bool { return true; }
        };

        // Create mock actual
        $actualMock = new class {
            public function getAccountCode(): string { return '5000'; }
            public function getAccountName(): string { return 'Salaries'; }
            public function getCostCenterId(): string { return 'cc-001'; }
            public function getCostCenterName(): string { return 'Operations'; }
            public function getBalance(): string { return '45000.00'; } // Under budget = favorable for expense
            public function getCurrency(): string { return 'USD'; }
            public function getDebitTotal(): string { return '45000.00'; }
            public function getCreditTotal(): string { return '0'; }
        };

        $this->budgetQueryMock
            ->expects($this->once())
            ->method('getBudgetsByPeriod')
            ->willReturn([$budgetMock]);

        $this->glQueryMock
            ->expects($this->once())
            ->method('getActualsByPeriod')
            ->willReturn([$actualMock]);

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $result = $provider->getVarianceAnalysis('tenant-001', '2026-01');

        $this->assertIsArray($result);
        $this->assertEquals('2026-01', $result['period_id']);
        $this->assertEquals('50000', $result['total_budgeted']);
        $this->assertEquals('45000', $result['total_actual']);
        $this->assertEquals('5000', $result['total_variance']); // Budget - Actual
        $this->assertCount(1, $result['variances']);
        $this->assertTrue($result['variances'][0]['is_favorable']);
    }

    /**
     * Test variance analysis with revenue account.
     */
    public function testGetVarianceAnalysisWithRevenueAccount(): void
    {
        // Create mock budget for revenue account
        $budgetMock = new class {
            public function getId(): string { return 'budget-002'; }
            public function getName(): string { return 'Revenue Budget'; }
            public function getCostCenterId(): string { return 'cc-001'; }
            public function getCostCenterName(): string { return 'Sales'; }
            public function getAccountCode(): string { return '4000'; } // Revenue account
            public function getAccountName(): string { return 'Sales Revenue'; }
            public function getAmount(): string { return '100000.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getVersion(): string { return 'v1'; }
            public function getVersionName(): string { return 'Original'; }
            public function isOriginal(): bool { return true; }
        };

        // For account 4000 (prefix '4'), positive variance is favorable
        // variance = 100000 - 80000 = 20000 >= 0 = favorable
        $actualMock = new class {
            public function getAccountCode(): string { return '4000'; }
            public function getAccountName(): string { return 'Sales Revenue'; }
            public function getCostCenterId(): string { return 'cc-001'; }
            public function getCostCenterName(): string { return 'Sales'; }
            public function getBalance(): string { return '80000.00'; } // Under budget = favorable for revenue
            public function getCurrency(): string { return 'USD'; }
            public function getDebitTotal(): string { return '0'; }
            public function getCreditTotal(): string { return '80000.00'; }
        };

        $this->budgetQueryMock
            ->expects($this->once())
            ->method('getBudgetsByPeriod')
            ->willReturn([$budgetMock]);

        $this->glQueryMock
            ->expects($this->once())
            ->method('getActualsByPeriod')
            ->willReturn([$actualMock]);

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $result = $provider->getVarianceAnalysis('tenant-001', '2026-01');

        $this->assertTrue($result['variances'][0]['is_favorable']);
    }

    /**
     * Test variance analysis throws exception and logs error.
     */
    public function testGetVarianceAnalysisThrowsAndLogsError(): void
    {
        $this->budgetQueryMock
            ->expects($this->once())
            ->method('getBudgetsByPeriod')
            ->willThrowException(new \RuntimeException('Database error'));

        // First call from getVarianceAnalysis (debug)
        // Second call from getBudgetData (debug)
        $this->loggerMock
            ->expects($this->exactly(2))
            ->method('debug');

        $this->loggerMock
            ->expects($this->exactly(2))
            ->method('error');

        $this->expectException(\RuntimeException::class);

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $this->costQueryMock,
            $this->loggerMock
        );

        $provider->getVarianceAnalysis('tenant-001', '2026-01');
    }

    // =========================================================================
    // Test Suite: Provider with Cost Query
    // =========================================================================

    /**
     * Test provider can be instantiated with cost query.
     */
    public function testProviderCanBeInstantiatedWithCostQuery(): void
    {
        $costQueryMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCommittedCosts'])
            ->getMock();

        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            $costQueryMock,
            $this->loggerMock
        );

        $this->assertInstanceOf(BudgetVarianceProvider::class, $provider);
    }

    /**
     * Test provider can be instantiated with null cost query.
     */
    public function testProviderCanBeInstantiatedWithNullCostQuery(): void
    {
        $provider = new BudgetVarianceProvider(
            $this->budgetQueryMock,
            $this->glQueryMock,
            null,
            $this->loggerMock
        );

        $this->assertInstanceOf(BudgetVarianceProvider::class, $provider);
    }
}
