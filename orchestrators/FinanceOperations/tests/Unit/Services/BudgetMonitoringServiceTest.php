<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FinanceOperations\Services\BudgetMonitoringService;
use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckResult;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetVarianceRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetVarianceResult;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetThresholdRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetThresholdResult;
use Nexus\FinanceOperations\Exceptions\BudgetTrackingException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for BudgetMonitoringService.
 *
 * Tests cover:
 * - Budget availability checks (sufficient funds, insufficient funds, high utilization)
 * - Variance calculations (with filters for cost center and project)
 * - Threshold checking (multiple exceeded thresholds)
 * - Error handling (provider exceptions, budget not found, variance calculation failed)
 * - Data retrieval methods (getBudgetData, getActualData)
 *
 * @since 1.0.0
 */
final class BudgetMonitoringServiceTest extends TestCase
{
    private MockObject|BudgetVarianceProviderInterface $dataProviderMock;
    private MockObject|LoggerInterface $loggerMock;
    private BudgetMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataProviderMock = $this->createMock(BudgetVarianceProviderInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->service = new BudgetMonitoringService(
            $this->dataProviderMock,
            $this->loggerMock
        );
    }

    /**
     * Test that service can be instantiated with default logger.
     */
    public function testServiceCanBeInstantiatedWithDefaultLogger(): void
    {
        $service = new BudgetMonitoringService($this->dataProviderMock);
        
        $this->assertInstanceOf(BudgetMonitoringService::class, $service);
    }

    // =========================================================================
    // Test Suite: checkAvailability() method
    // =========================================================================

    /**
     * Scenario 1: Budget availability check with sufficient funds.
     *
     * Tests that when budget has enough available funds, the result
     * indicates availability is true and no warning is set.
     */
    public function testCheckAvailabilityWithSufficientFunds(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '500.00',
            costCenterId: null,
            accountId: null
        );

        $budgetData = [
            'budgeted' => '1000.00',
            'actual' => '200.00',
            'committed' => '100.00',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '', 'budget-001')
            ->willReturn($budgetData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Checking budget availability', $this->anything());

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertTrue($result->available, 'Budget should be available when requested amount is within available funds');
        $this->assertEquals('budget-001', $result->budgetId);
        $this->assertEquals('1000.00', $result->budgeted);
        $this->assertEquals('200.00', $result->actual);
        $this->assertEquals('100.00', $result->committed);
        $this->assertEquals('700', $result->availableAmount);
        $this->assertEquals(30.0, $result->utilizationPercent);
        $this->assertNull($result->warning, 'No warning should be set when utilization is below 80%');
    }

    /**
     * Scenario 2: Budget availability check with insufficient funds.
     *
     * Tests that when requested amount exceeds available budget,
     * the result indicates unavailability with a warning message.
     */
    public function testCheckAvailabilityWithInsufficientFunds(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '800.00',
            costCenterId: null,
            accountId: null
        );

        $budgetData = [
            'budgeted' => '1000.00',
            'actual' => '200.00',
            'committed' => '100.00',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '', 'budget-001')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertFalse($result->available, 'Budget should not be available when requested amount exceeds available funds');
        $this->assertNotNull($result->warning, 'Warning should be set when funds are insufficient');
        $this->assertStringContainsString('Insufficient budget', $result->warning);
        $this->assertStringContainsString('800', $result->warning);
        $this->assertStringContainsString('700', $result->warning);
    }

    /**
     * Scenario 3: Budget availability check with high utilization warning (≥80%).
     *
     * Tests that when budget utilization is at or above 80%, a warning
     * is returned even if the requested amount is available.
     */
    public function testCheckAvailabilityWithHighUtilizationWarning(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '50.00', // Small amount that is available
            costCenterId: null,
            accountId: null
        );

        // Budgeted: 1000, Actual: 700, Committed: 100 = Utilization 80%
        $budgetData = [
            'budgeted' => '1000.00',
            'actual' => '700.00',
            'committed' => '100.00',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '', 'budget-001')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertTrue($result->available, 'Budget should be available for the requested amount');
        $this->assertEquals(80.0, $result->utilizationPercent);
        $this->assertNotNull($result->warning, 'Warning should be set when utilization is at or above 80%');
        $this->assertStringContainsString('Budget utilization at', $result->warning);
        $this->assertStringContainsString('80.0%', $result->warning);
    }

    /**
     * Tests budget check when data is returned as budget lines (not direct values).
     * This tests the alternative data structure path in checkAvailability.
     */
    public function testCheckAvailabilityWithBudgetLines(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-line-001',
            costCenterId: 'cc-001',
            accountId: null,
            amount: '100.00'
        );

        $budgetData = [
            'lines' => [
                [
                    'budget_id' => 'budget-line-001',
                    'cost_center_id' => 'cc-001',
                    'budgeted' => '500.00',
                    'actual' => '200.00',
                    'committed' => '50.00',
                ],
                [
                    'budget_id' => 'budget-line-002',
                    'cost_center_id' => 'cc-002',
                    'budgeted' => '1000.00',
                    'actual' => '100.00',
                    'committed' => '0',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '', 'budget-line-001')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertTrue($result->available);
        $this->assertEquals('500.00', $result->budgeted);
        $this->assertEquals('200.00', $result->actual);
        $this->assertEquals('50.00', $result->committed);
        $this->assertEquals('250', $result->availableAmount);
        $this->assertEquals(50.0, $result->utilizationPercent);
    }

    /**
     * Tests budget check matching by cost center when budget ID doesn't match directly.
     */
    public function testCheckAvailabilityMatchesByCostCenter(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'non-existent-budget',
            costCenterId: 'cc-001',
            accountId: null,
            amount: '50.00'
        );

        $budgetData = [
            'lines' => [
                [
                    'id' => 'line-001',
                    'cost_center_id' => 'cc-001',
                    'budgeted' => '300.00',
                    'actual' => '100.00',
                    'committed' => '50.00',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '', 'non-existent-budget')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertTrue($result->available);
        $this->assertEquals('300.00', $result->budgeted);
    }

    /**
     * Tests budget check matching by account ID.
     */
    public function testCheckAvailabilityMatchesByAccountId(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'some-budget',
            costCenterId: null,
            accountId: 'acc-001',
            amount: '50.00'
        );

        $budgetData = [
            'lines' => [
                [
                    'id' => 'line-001',
                    'cost_center_id' => 'cc-001',
                    'account_id' => 'acc-001',
                    'budgeted' => '300.00',
                    'actual' => '100.00',
                    'committed' => '0',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '', 'some-budget')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertTrue($result->available);
        $this->assertEquals('300.00', $result->budgeted);
    }

    /**
     * Tests budget check when no matching line is found in budget lines.
     * Should use default values.
     */
    public function testCheckAvailabilityWithNoMatchingLine(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '100.00'
        );

        $budgetData = [
            'lines' => [
                [
                    'budget_id' => 'different-budget',
                    'cost_center_id' => 'cc-002',
                    'budgeted' => '500.00',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert - should use default values when no match found
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertFalse($result->available);
        $this->assertEquals('0', $result->budgeted);
    }

    /**
     * Scenario 7: Budget not found exception handling.
     *
     * Tests that when the data provider throws an exception during
     * availability check, a BudgetTrackingException is thrown.
     */
    public function testCheckAvailabilityThrowsBudgetNotFoundException(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '100.00'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Budget availability check failed', $this->anything());

        // Assert
        $this->expectException(BudgetTrackingException::class);

        // Act
        $this->service->checkAvailability($request);
    }

    // =========================================================================
    // Test Suite: calculateVariances() method
    // =========================================================================

    /**
     * Scenario 4: Variance calculation with filtering by cost center/project.
     *
     * Tests that variance calculation correctly filters results
     * when cost center is specified.
     */
    public function testCalculateVariancesWithCostCenterFilter(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            budgetId: null,
            costCenterId: 'cc-001',
            projectId: null
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'budget_name' => 'Marketing Budget',
                    'cost_center_id' => 'cc-001',
                    'project_id' => 'proj-001',
                    'budgeted' => '5000.00',
                    'actual' => '4500.00',
                ],
                [
                    'budget_id' => 'budget-002',
                    'budget_name' => 'IT Budget',
                    'cost_center_id' => 'cc-002',
                    'project_id' => null,
                    'budgeted' => '3000.00',
                    'actual' => '2800.00',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->with('tenant-001', '2024-Q1', null)
            ->willReturn($varianceData);

        // Act
        $result = $this->service->calculateVariances($request);

        // Assert
        $this->assertInstanceOf(BudgetVarianceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(1, $result->variances, 'Should only return variances for the specified cost center');
        $this->assertEquals('cc-001', $result->variances[0]['cost_center_id']);
        $this->assertEquals('5000', $result->totalBudgeted);
        $this->assertEquals('4500', $result->totalActual);
        $this->assertEquals('500', $result->totalVariance);
    }

    /**
     * Tests variance calculation with filtering by project.
     */
    public function testCalculateVariancesWithProjectFilter(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            budgetId: null,
            costCenterId: null,
            projectId: 'proj-001'
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'cost_center_id' => 'cc-001',
                    'project_id' => 'proj-001',
                    'budgeted' => '5000.00',
                    'actual' => '4500.00',
                ],
                [
                    'budget_id' => 'budget-002',
                    'cost_center_id' => 'cc-001',
                    'project_id' => 'proj-002',
                    'budgeted' => '2000.00',
                    'actual' => '1800.00',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->with('tenant-001', '2024-Q1', null)
            ->willReturn($varianceData);

        // Act
        $result = $this->service->calculateVariances($request);

        // Assert
        $this->assertInstanceOf(BudgetVarianceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(1, $result->variances);
        $this->assertEquals('proj-001', $result->variances[0]['project_id']);
    }

    /**
     * Tests variance calculation without any filters - returns all variances.
     */
    public function testCalculateVariancesWithoutFilters(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1'
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'cost_center_id' => 'cc-001',
                    'project_id' => null,
                    'budgeted' => '5000.00',
                    'actual' => '4500.00',
                ],
                [
                    'budget_id' => 'budget-002',
                    'cost_center_id' => 'cc-002',
                    'project_id' => null,
                    'budgeted' => '3000.00',
                    'actual' => '2800.00',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->with('tenant-001', '2024-Q1', null)
            ->willReturn($varianceData);

        // Act
        $result = $this->service->calculateVariances($request);

        // Assert
        $this->assertInstanceOf(BudgetVarianceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(2, $result->variances);
        $this->assertEquals('8000', $result->totalBudgeted);
        $this->assertEquals('7300', $result->totalActual);
        $this->assertEquals('700', $result->totalVariance);
    }

    /**
     * Tests variance calculation with a specific budget ID filter.
     */
    public function testCalculateVariancesWithBudgetIdFilter(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            budgetId: 'budget-001'
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'cost_center_id' => 'cc-001',
                    'project_id' => null,
                    'budgeted' => '5000.00',
                    'actual' => '4500.00',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->with('tenant-001', '2024-Q1', 'budget-001')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->calculateVariances($request);

        // Assert
        $this->assertInstanceOf(BudgetVarianceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(1, $result->variances);
    }

    /**
     * Tests variance calculation handles empty variances array.
     */
    public function testCalculateVariancesWithEmptyVariances(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1'
        );

        $varianceData = ['variances' => []];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->calculateVariances($request);

        // Assert
        $this->assertInstanceOf(BudgetVarianceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(0, $result->variances);
        $this->assertEquals('0', $result->totalBudgeted);
        $this->assertEquals('0', $result->totalActual);
        $this->assertEquals('0', $result->totalVariance);
    }

    /**
     * Scenario 8: Variance calculation failed exception handling.
     *
     * Tests that when variance calculation fails, a BudgetTrackingException
     * is thrown with the proper context.
     */
    public function testCalculateVariancesThrowsExceptionOnFailure(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willThrowException(new \RuntimeException('Calculation engine unavailable'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Variance calculation failed', $this->anything());

        // Assert
        $this->expectException(BudgetTrackingException::class);

        // Act
        $this->service->calculateVariances($request);
    }

    // =========================================================================
    // Test Suite: checkThresholds() method
    // =========================================================================

    /**
     * Scenario 5: Threshold checking with multiple exceeded thresholds.
     *
     * Tests that threshold checking correctly identifies budgets
     * that exceed defined threshold percentages.
     */
    public function testCheckThresholdsWithExceededThresholds(): void
    {
        // Arrange
        $request = new BudgetThresholdRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            thresholds: [80, 90, 100],
            costCenterId: null
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'budget_name' => 'Marketing Budget',
                    'cost_center_id' => 'cc-001',
                    'budgeted' => '10000.00',
                    'actual' => '9500.00', // 95% utilization
                ],
                [
                    'budget_id' => 'budget-002',
                    'budget_name' => 'IT Budget',
                    'cost_center_id' => 'cc-002',
                    'budgeted' => '5000.00',
                    'actual' => '3500.00', // 70% utilization
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->checkThresholds($request);

        // Assert
        $this->assertInstanceOf(BudgetThresholdResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(1, $result->exceededThresholds, 'Only budget-001 should exceed thresholds');
        $this->assertEquals('budget-001', $result->exceededThresholds[0]['budgetId']);
        $this->assertEquals(90, $result->exceededThresholds[0]['threshold'], 'Should report the highest threshold exceeded (90%, not 100%)');
        $this->assertEquals(95.0, $result->exceededThresholds[0]['utilizationPercent']);
        $this->assertCount(1, $result->warnings);
    }

    /**
     * Tests threshold checking with cost center filter.
     */
    public function testCheckThresholdsWithCostCenterFilter(): void
    {
        // Arrange
        $request = new BudgetThresholdRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            thresholds: [80, 90],
            costCenterId: 'cc-001'
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'budget_name' => 'Marketing Budget',
                    'cost_center_id' => 'cc-001',
                    'budgeted' => '10000.00',
                    'actual' => '9000.00', // 90% utilization
                ],
                [
                    'budget_id' => 'budget-002',
                    'budget_name' => 'IT Budget',
                    'cost_center_id' => 'cc-002',
                    'budgeted' => '5000.00',
                    'actual' => '4900.00', // 98% utilization
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->checkThresholds($request);

        // Assert
        $this->assertInstanceOf(BudgetThresholdResult::class, $result);
        $this->assertTrue($result->success);
        // Should only check cc-001 variance
        $this->assertCount(1, $result->exceededThresholds);
        $this->assertEquals('cc-001', $result->exceededThresholds[0]['costCenterId']);
    }

    /**
     * Tests threshold checking when no thresholds are exceeded.
     */
    public function testCheckThresholdsWithNoExceededThresholds(): void
    {
        // Arrange
        $request = new BudgetThresholdRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            thresholds: [80, 90, 100]
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'budget_name' => 'Marketing Budget',
                    'budgeted' => '10000.00',
                    'actual' => '5000.00', // 50% utilization
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->checkThresholds($request);

        // Assert
        $this->assertInstanceOf(BudgetThresholdResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(0, $result->exceededThresholds);
        $this->assertCount(0, $result->warnings);
    }

    /**
     * Tests threshold checking handles zero budgeted amounts gracefully.
     */
    public function testCheckThresholdsWithZeroBudgetedAmount(): void
    {
        // Arrange
        $request = new BudgetThresholdRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            thresholds: [80, 90]
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'budgeted' => '0',
                    'actual' => '0',
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->checkThresholds($request);

        // Assert
        $this->assertInstanceOf(BudgetThresholdResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(0, $result->exceededThresholds, 'Zero budgeted amounts should be skipped');
    }

    /**
     * Tests threshold checking handles exception gracefully.
     * Unlike other methods, checkThresholds returns error in result instead of throwing.
     */
    public function testCheckThresholdsHandlesExceptionGracefully(): void
    {
        // Arrange
        $request = new BudgetThresholdRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            thresholds: [80]
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Threshold check failed', $this->anything());

        // Act
        $result = $this->service->checkThresholds($request);

        // Assert - should return error result instead of throwing exception
        $this->assertInstanceOf(BudgetThresholdResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Database error', $result->error);
    }

    // =========================================================================
    // Test Suite: getBudgetData() method
    // =========================================================================

    /**
     * Tests getBudgetData method returns data from provider.
     */
    public function testGetBudgetDataReturnsProviderData(): void
    {
        // Arrange
        $expectedData = [
            'budgeted' => '10000.00',
            'actual' => '5000.00',
            'lines' => [],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '2024-Q1', 'budget-001')
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getBudgetData('tenant-001', '2024-Q1', 'budget-001');

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    /**
     * Tests getBudgetData with null budget ID.
     */
    public function testGetBudgetDataWithNullBudgetId(): void
    {
        // Arrange
        $expectedData = ['budgeted' => '5000.00'];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->with('tenant-001', '2024-Q1', null)
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getBudgetData('tenant-001', '2024-Q1', null);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    // =========================================================================
    // Test Suite: getActualData() method
    // =========================================================================

    /**
     * Tests getActualData method returns data from provider.
     */
    public function testGetActualDataReturnsProviderData(): void
    {
        // Arrange
        $expectedData = [
            'period_id' => '2024-Q1',
            'transactions' => [
                ['amount' => '100.00', 'date' => '2024-01-15'],
                ['amount' => '200.00', 'date' => '2024-01-20'],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getActualData')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getActualData('tenant-001', '2024-Q1');

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    // =========================================================================
    // Edge Cases and Additional Scenarios
    // =========================================================================

    /**
     * Tests edge case: budget data with zero values.
     */
    public function testCheckAvailabilityWithZeroBudget(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '100.00'
        );

        $budgetData = [
            'budgeted' => '0',
            'actual' => '0',
            'committed' => '0',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertFalse($result->available);
        $this->assertEquals(0.0, $result->utilizationPercent);
        $this->assertEquals('0', $result->availableAmount);
    }

    /**
     * Tests that utilization percentage is calculated correctly for high precision.
     */
    public function testUtilizationPercentageCalculation(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '10.00'
        );

        // Budgeted: 1000, Actual: 333, Committed: 333 = 66.6%
        $budgetData = [
            'budgeted' => '1000.00',
            'actual' => '333.00',
            'committed' => '333.00',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertEquals(66.6, $result->utilizationPercent);
    }

    /**
     * Tests that request amount is properly compared as float.
     */
    public function testCheckAvailabilityWithFloatAmountComparison(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '100.50'
        );

        $budgetData = [
            'budgeted' => '1000.00',
            'actual' => '800.00',
            'committed' => '99.50', // 800 + 99.50 = 899.50, available = 100.50
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->willReturn($budgetData);

        // Act
        $result = $this->service->checkAvailability($request);

        // Assert
        $this->assertInstanceOf(BudgetCheckResult::class, $result);
        $this->assertTrue($result->available, 'Amount 100.50 should be available when 100.50 is available');
    }

    /**
     * Tests variance calculation with missing data in variance records.
     */
    public function testCalculateVariancesWithMissingData(): void
    {
        // Arrange
        $request = new BudgetVarianceRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1'
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    // Missing budgeted and actual - should default to 0
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->calculateVariances($request);

        // Assert
        $this->assertInstanceOf(BudgetVarianceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('0', $result->totalBudgeted);
        $this->assertEquals('0', $result->totalActual);
    }

    /**
     * Tests threshold checking with custom threshold values.
     */
    public function testCheckThresholdsWithCustomThresholds(): void
    {
        // Arrange
        $request = new BudgetThresholdRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            thresholds: [50, 75], // Lower thresholds for testing
            costCenterId: null
        );

        $varianceData = [
            'variances' => [
                [
                    'budget_id' => 'budget-001',
                    'budget_name' => 'Test Budget',
                    'budgeted' => '1000.00',
                    'actual' => '600.00', // 60% utilization - exceeds 50%
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getVarianceAnalysis')
            ->willReturn($varianceData);

        // Act
        $result = $this->service->checkThresholds($request);

        $this->assertInstanceOf(BudgetThresholdResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(1, $result->exceededThresholds);
        $this->assertEquals(50, $result->exceededThresholds[0]['threshold'], 'Should report 50% threshold as it is the first exceeded');
    }

    /**
     * Tests logger is called with correct context across methods.
     */
    public function testLoggerIsCalledWithCorrectContext(): void
    {
        // Arrange - Test checkAvailability logging
        $checkRequest = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '100.00'
        );

        $this->dataProviderMock
            ->method('getBudgetData')
            ->willReturn(['budgeted' => '1000.00']);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Checking budget availability',
                $this->callback(function ($context) {
                    return $context['tenant_id'] === 'tenant-001'
                        && $context['budget_id'] === 'budget-001'
                        && $context['amount'] === '100.00';
                })
            );

        // Act
        $this->service->checkAvailability($checkRequest);
    }

    /**
     * Tests logger error is called when checkAvailability fails.
     */
    public function testLoggerErrorCalledOnCheckAvailabilityFailure(): void
    {
        // Arrange
        $request = new BudgetCheckRequest(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '100.00'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBudgetData')
            ->willThrowException(new \RuntimeException('Connection timeout'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Budget availability check failed',
                $this->callback(function ($context) {
                    return $context['tenant_id'] === 'tenant-001'
                        && $context['budget_id'] === 'budget-001'
                        && $context['error'] === 'Connection timeout';
                })
            );

        // Assert & Act
        try {
            $this->service->checkAvailability($request);
        } catch (BudgetTrackingException $e) {
            // Expected
        }
    }
}
