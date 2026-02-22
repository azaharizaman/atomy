<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FinanceOperations\Services\DepreciationRunService;
use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunRequest;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunResult;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationScheduleRequest;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationScheduleResult;
use Nexus\FinanceOperations\Exceptions\DepreciationCoordinationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for DepreciationRunService.
 *
 * Tests cover:
 * - Straight-line depreciation calculation
 * - Declining balance depreciation calculation
 * - Sum-of-years depreciation calculation
 * - Fully depreciated asset handling
 * - Empty asset list handling
 * - Validate-only mode (no GL posting)
 * - GL posting enabled mode
 * - Schedule generation for asset
 * - Error handling when data provider throws exceptions
 * - Depreciation run failed exception handling
 * - Schedule generation failed exception handling
 *
 * @since 1.0.0
 */
final class DepreciationRunServiceTest extends TestCase
{
    private MockObject|DepreciationDataProviderInterface $dataProviderMock;
    private MockObject|LoggerInterface $loggerMock;
    private DepreciationRunService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataProviderMock = $this->createMock(DepreciationDataProviderInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->service = new DepreciationRunService(
            $this->dataProviderMock,
            $this->loggerMock
        );
    }

    // =========================================================================
    // Test Suite: Service Instantiation
    // =========================================================================

    /**
     * Test that service can be instantiated with default logger.
     */
    public function testServiceCanBeInstantiatedWithDefaultLogger(): void
    {
        $service = new DepreciationRunService($this->dataProviderMock);
        
        $this->assertInstanceOf(DepreciationRunService::class, $service);
    }

    // =========================================================================
    // Test Suite: executeRun() - Straight-Line Depreciation
    // =========================================================================

    /**
     * Test straight-line depreciation calculation.
     */
    public function testExecuteRunWithStraightLineDepreciation(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-001',
                'asset_code' => 'FA-001',
                'asset_name' => 'Test Asset',
                'book_value' => 100000.0,
                'original_cost' => 120000.0,
                'accumulated_depreciation' => 20000.0,
                'salvage_value' => 10000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-001'])
            ->willReturn(['assets' => $assetData]);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Executing depreciation run',
                $this->callback(function (array $context) use ($tenantId, $periodId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['period_id'] === $periodId
                        && $context['asset_count'] === 1;
                })
            );

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-001'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertStringStartsWith('DEPR-', $result->runId);
        $this->assertEquals(1, $result->assetsProcessed);
        $this->assertGreaterThan(0, (float)$result->totalDepreciation);
        $this->assertCount(1, $result->assetDetails);
        
        // Verify asset details
        $assetDetail = $result->assetDetails[0];
        $this->assertEquals('asset-001', $assetDetail['assetId']);
        $this->assertEquals('FA-001', $assetDetail['assetCode']);
        $this->assertEquals('Test Asset', $assetDetail['assetName']);
    }

    // =========================================================================
    // Test Suite: executeRun() - Declining Balance Depreciation
    // =========================================================================

    /**
     * Test declining balance depreciation calculation.
     */
    public function testExecuteRunWithDecliningBalanceDepreciation(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-002',
                'asset_code' => 'FA-002',
                'asset_name' => 'Declining Asset',
                'book_value' => 80000.0,
                'original_cost' => 100000.0,
                'accumulated_depreciation' => 20000.0,
                'salvage_value' => 10000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'declining_balance',
                'declining_rate' => 2.0,
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-002'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-002'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->assetsProcessed);
        $this->assertGreaterThan(0, (float)$result->totalDepreciation);
    }

    // =========================================================================
    // Test Suite: executeRun() - Sum-of-Years Depreciation
    // =========================================================================

    /**
     * Test sum-of-years digits depreciation calculation.
     */
    public function testExecuteRunWithSumOfYearsDepreciation(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-003',
                'asset_code' => 'FA-003',
                'asset_name' => 'SOYD Asset',
                'book_value' => 80000.0,
                'original_cost' => 100000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 10000.0,
                'useful_life_months' => 60, // 5 years
                'depreciation_method' => 'sum_of_years',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-003'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-003'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->assetsProcessed);
    }

    // =========================================================================
    // Test Suite: executeRun() - Fully Depreciated Asset
    // =========================================================================

    /**
     * Test fully depreciated asset returns zero depreciation.
     */
    public function testExecuteRunWithFullyDepreciatedAssetReturnsZero(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        // Book value equals salvage value - fully depreciated
        $assetData = [
            [
                'asset_id' => 'asset-004',
                'asset_code' => 'FA-004',
                'asset_name' => 'Fully Depreciated Asset',
                'book_value' => 10000.0,
                'original_cost' => 100000.0,
                'accumulated_depreciation' => 90000.0,
                'salvage_value' => 10000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-004'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-004'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        // Fully depreciated assets are skipped, so 0 assets processed
        $this->assertEquals(0, $result->assetsProcessed);
        $this->assertEquals('0', $result->totalDepreciation);
    }

    // =========================================================================
    // Test Suite: executeRun() - Empty Asset List
    // =========================================================================

    /**
     * Test empty asset list handling returns empty result.
     */
    public function testExecuteRunWithEmptyAssetList(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: [], // Empty asset list
            postToGL: false,
            validateOnly: false,
        );

        // When assetIds is empty, it will try to get from summary
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationRunSummary')
            ->with($tenantId, $periodId)
            ->willReturn(['active_asset_ids' => []]);

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertStringStartsWith('DEPR-', $result->runId);
        $this->assertEquals(0, $result->assetsProcessed);
        $this->assertEquals('0', $result->totalDepreciation);
        $this->assertEmpty($result->assetDetails);
    }

    // =========================================================================
    // Test Suite: executeRun() - Validate-Only Mode
    // =========================================================================

    /**
     * Test validate-only mode does not post to GL.
     */
    public function testExecuteRunWithValidateOnlyModeNoGLPosting(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-005',
                'asset_code' => 'FA-005',
                'asset_name' => 'Validate Asset',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-005'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-005'],
            postToGL: true, // Even if true, validateOnly overrides
            validateOnly: true, // Validate only - no GL posting
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->assetsProcessed);
        // No journal entries in validate-only mode
        $this->assertEmpty($result->journalEntries);
    }

    // =========================================================================
    // Test Suite: executeRun() - GL Posting Enabled
    // =========================================================================

    /**
     * Test GL posting enabled mode creates journal entries.
     */
    public function testExecuteRunWithGLPostingEnabledCreatesJournalEntries(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-006',
                'asset_code' => 'FA-006',
                'asset_name' => 'GL Asset',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
                'depreciation_account' => 'DEPR_EXP_6000',
                'accumulated_account' => 'ACCUM_DEPR_1600',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-006'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-006'],
            postToGL: true, // GL posting enabled
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->assetsProcessed);
        
        // Verify journal entries were created
        $this->assertNotEmpty($result->journalEntries);
        $this->assertCount(1, $result->journalEntries);
        
        $journalEntry = $result->journalEntries[0];
        $this->assertEquals('asset-006', $journalEntry['asset_id']);
        $this->assertEquals('DEPR_EXP_6000', $journalEntry['depreciation_account']);
        $this->assertEquals('ACCUM_DEPR_1600', $journalEntry['accumulated_account']);
    }

    // =========================================================================
    // Test Suite: generateSchedule() - Schedule Generation
    // =========================================================================

    /**
     * Test schedule generation for asset.
     */
    public function testGenerateScheduleForAsset(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $assetId = 'asset-001';
        
        $existingSchedules = [
            'schedules' => [],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationSchedules')
            ->with($tenantId, $assetId)
            ->willReturn($existingSchedules);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Generating depreciation schedule',
                $this->callback(function (array $context) use ($assetId) {
                    return $context['asset_id'] === $assetId;
                })
            );

        $request = new DepreciationScheduleRequest(
            tenantId: $tenantId,
            assetId: $assetId,
            depreciationMethod: 'straight_line',
            usefulLifeYears: 5,
            salvageValue: '10000',
        );

        // Act
        $result = $this->service->generateSchedule($request);

        // Assert
        $this->assertInstanceOf(DepreciationScheduleResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($assetId, $result->assetId);
        $this->assertNotEmpty($result->schedule);
        
        // 5 years * 12 months = 60 periods
        $this->assertCount(60, $result->schedule);
        
        // Verify schedule structure
        $firstPeriod = $result->schedule[0];
        $this->assertArrayHasKey('period', $firstPeriod);
        $this->assertArrayHasKey('periodStart', $firstPeriod);
        $this->assertArrayHasKey('periodEnd', $firstPeriod);
        $this->assertArrayHasKey('depreciation', $firstPeriod);
        $this->assertArrayHasKey('accumulatedDepreciation', $firstPeriod);
        $this->assertArrayHasKey('netBookValue', $firstPeriod);
        
        // Verify total depreciation is calculated
        $this->assertGreaterThan(0, (float)$result->totalDepreciation);
    }

    // =========================================================================
    // Test Suite: executeRun() - Error Handling
    // =========================================================================

    /**
     * Test error handling when data provider throws exception.
     */
    public function testExecuteRunHandlesDataProviderException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-error'])
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Depreciation run failed',
                $this->callback(function (array $context) use ($tenantId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['error'] === 'Database connection failed';
                })
            );

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-error'],
            postToGL: false,
            validateOnly: false,
        );

        // Act & Assert
        $this->expectException(DepreciationCoordinationException::class);
        $this->expectExceptionMessage('Depreciation run failed for period 2026-01: Database connection failed');
        
        $this->service->executeRun($request);
    }

    // =========================================================================
    // Test Suite: generateSchedule() - Error Handling
    // =========================================================================

    /**
     * Test schedule generation handles data provider exception.
     */
    public function testGenerateScheduleHandlesDataProviderException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $assetId = 'asset-error';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationSchedules')
            ->with($tenantId, $assetId)
            ->willThrowException(new \RuntimeException('Asset not found'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Schedule generation failed',
                $this->callback(function (array $context) use ($assetId) {
                    return $context['asset_id'] === $assetId 
                        && $context['error'] === 'Asset not found';
                })
            );

        $request = new DepreciationScheduleRequest(
            tenantId: $tenantId,
            assetId: $assetId,
            depreciationMethod: 'straight_line',
            usefulLifeYears: 5,
            salvageValue: '10000',
        );

        // Act & Assert
        $this->expectException(DepreciationCoordinationException::class);
        $this->expectExceptionMessage('Depreciation schedule generation failed for asset asset-error: Asset not found');
        
        $this->service->generateSchedule($request);
    }

    // =========================================================================
    // Test Suite: getRunSummary() - Basic Functionality
    // =========================================================================

    /**
     * Test getRunSummary returns summary data.
     */
    public function testGetRunSummaryReturnsSummaryData(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $summaryData = [
            'period_id' => $periodId,
            'total_assets' => 10,
            'active_asset_ids' => ['asset-001', 'asset-002', 'asset-003'],
            'total_depreciation' => '5000.00',
            'last_run_date' => '2026-01-31',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationRunSummary')
            ->with($tenantId, $periodId)
            ->willReturn($summaryData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Getting depreciation run summary',
                $this->callback(function (array $context) use ($tenantId, $periodId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['period_id'] === $periodId;
                })
            );

        // Act
        $result = $this->service->getRunSummary($tenantId, $periodId);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($periodId, $result['period_id']);
        $this->assertEquals(10, $result['total_assets']);
        $this->assertCount(3, $result['active_asset_ids']);
        $this->assertEquals('5000.00', $result['total_depreciation']);
    }

    // =========================================================================
    // Test Suite: executeRun() - Get Assets from Summary
    // =========================================================================

    /**
     * Test executeRun gets assets from summary when assetIds is empty.
     */
    public function testExecuteRunGetsAssetsFromSummaryWhenEmptyAssetIds(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $summaryData = [
            'active_asset_ids' => ['asset-summary-1', 'asset-summary-2'],
        ];

        $assetData = [
            [
                'asset_id' => 'asset-summary-1',
                'asset_code' => 'FA-SUM-1',
                'asset_name' => 'Summary Asset 1',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
            [
                'asset_id' => 'asset-summary-2',
                'asset_code' => 'FA-SUM-2',
                'asset_name' => 'Summary Asset 2',
                'book_value' => 30000.0,
                'original_cost' => 40000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 3000.0,
                'useful_life_months' => 48,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationRunSummary')
            ->with($tenantId, $periodId)
            ->willReturn($summaryData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-summary-1', 'asset-summary-2'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: [], // Empty - should get from summary
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->assetsProcessed);
    }

    // =========================================================================
    // Test Suite: executeRun() - Custom Depreciation Account
    // =========================================================================

    /**
     * Test executeRun uses custom depreciation account from asset data.
     */
    public function testExecuteRunUsesCustomDepreciationAccount(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'id' => 'asset-007',
                'code' => 'FA-007',
                'name' => 'Custom Account Asset',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
                'depreciation_expense_account' => 'CUSTOM_DEPR_7000',
                'accumulated_depreciation_account' => 'CUSTOM_ACCUM_1700',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-007'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-007'],
            postToGL: true,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        
        $journalEntry = $result->journalEntries[0];
        $this->assertEquals('CUSTOM_DEPR_7000', $journalEntry['depreciation_account']);
        $this->assertEquals('CUSTOM_ACCUM_1700', $journalEntry['accumulated_account']);
    }

    // =========================================================================
    // Test Suite: executeRun() - Default Account Fallback
    // =========================================================================

    /**
     * Test executeRun uses default accounts when not specified in asset data.
     */
    public function testExecuteRunUsesDefaultAccountsWhenNotSpecified(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-008',
                'asset_code' => 'FA-008',
                'asset_name' => 'Default Account Asset',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
                // No custom accounts specified
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-008'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-008'],
            postToGL: true,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        
        $journalEntry = $result->journalEntries[0];
        $this->assertEquals('DEPRECIATION_EXPENSE', $journalEntry['depreciation_account']);
        $this->assertEquals('ACCUMULATED_DEPRECIATION', $journalEntry['accumulated_account']);
    }

    // =========================================================================
    // Test Suite: executeRun() - Accumulated Depreciation Exceeds Cost
    // =========================================================================

    /**
     * Test executeRun returns zero when accumulated depreciation exceeds depreciable amount.
     */
    public function testExecuteRunReturnsZeroWhenAccumulatedExceedsCost(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-009',
                'asset_code' => 'FA-009',
                'asset_name' => 'Over Depreciated Asset',
                'book_value' => 2000.0,
                'original_cost' => 10000.0,
                'accumulated_depreciation' => 9500.0, // More than (10000 - 5000)
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-009'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-009'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        // Asset with remainingAmount <= 0 is skipped
        $this->assertEquals(0, $result->assetsProcessed);
    }

    // =========================================================================
    // Test Suite: generateSchedule() - Declining Balance Method
    // =========================================================================

    /**
     * Test schedule generation with declining balance method.
     */
    public function testGenerateScheduleWithDecliningBalanceMethod(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $assetId = 'asset-declining';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationSchedules')
            ->with($tenantId, $assetId)
            ->willReturn(['schedules' => []]);

        $request = new DepreciationScheduleRequest(
            tenantId: $tenantId,
            assetId: $assetId,
            depreciationMethod: 'declining_balance',
            usefulLifeYears: 3,
            salvageValue: '5000',
        );

        // Act
        $result = $this->service->generateSchedule($request);

        // Assert
        $this->assertInstanceOf(DepreciationScheduleResult::class, $result);
        $this->assertTrue($result->success);
        // 3 years * 12 months = 36 periods
        $this->assertCount(36, $result->schedule);
    }

    // =========================================================================
    // Test Suite: generateSchedule() - Sum of Years Method
    // =========================================================================

    /**
     * Test schedule generation with sum-of-years digits method.
     */
    public function testGenerateScheduleWithSumOfYearsMethod(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $assetId = 'asset-soyd';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDepreciationSchedules')
            ->with($tenantId, $assetId)
            ->willReturn(['schedules' => []]);

        $request = new DepreciationScheduleRequest(
            tenantId: $tenantId,
            assetId: $assetId,
            depreciationMethod: 'sum_of_years',
            usefulLifeYears: 4,
            salvageValue: '2000',
        );

        // Act
        $result = $this->service->generateSchedule($request);

        // Assert
        $this->assertInstanceOf(DepreciationScheduleResult::class, $result);
        $this->assertTrue($result->success);
        // 4 years * 12 months = 48 periods
        $this->assertCount(48, $result->schedule);
    }

    // =========================================================================
    // Test Suite: executeRun() - With DepreciationBook
    // =========================================================================

    /**
     * Test executeRun with depreciation book parameter.
     */
    public function testExecuteRunWithDepreciationBook(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-book',
                'asset_code' => 'FA-BOOK',
                'asset_name' => 'Book Asset',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-book'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-book'],
            postToGL: false,
            validateOnly: false,
            depreciationBook: 'TAX_BOOK', // Tax book specified
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Test Suite: executeRun() - Book Values as Direct Array
    // =========================================================================

    /**
     * Test executeRun handles book values returned as direct array (not wrapped).
     */
    public function testExecuteRunHandlesDirectArrayBookValues(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        // Return as direct array, not wrapped in 'assets' key
        $assetData = [
            [
                'asset_id' => 'asset-direct',
                'asset_code' => 'FA-DIRECT',
                'asset_name' => 'Direct Array Asset',
                'book_value' => 50000.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-direct'])
            ->willReturn($assetData); // Direct array, not ['assets' => ...]

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-direct'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Test Suite: executeRun() - Zero Book Value
    // =========================================================================

    /**
     * Test executeRun handles asset with zero book value.
     */
    public function testExecuteRunHandlesZeroBookValue(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $assetData = [
            [
                'asset_id' => 'asset-zero',
                'asset_code' => 'FA-ZERO',
                'asset_name' => 'Zero Book Value Asset',
                'book_value' => 0.0,
                'original_cost' => 60000.0,
                'accumulated_depreciation' => 10000.0,
                'salvage_value' => 5000.0,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-zero'])
            ->willReturn(['assets' => $assetData]);

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-zero'],
            postToGL: false,
            validateOnly: false,
        );

        // Act
        $result = $this->service->executeRun($request);

        // Assert
        $this->assertInstanceOf(DepreciationRunResult::class, $result);
        $this->assertTrue($result->success);
        // Zero book value should result in zero depreciation
        $this->assertEquals(0, $result->assetsProcessed);
    }

    // =========================================================================
    // Test Suite: executeRun() - Re-throws DepreciationCoordinationException
    // =========================================================================

    /**
     * Test executeRun re-throws DepreciationCoordinationException without wrapping.
     */
    public function testExecuteRunReThrowsDepreciationCoordinationException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAssetBookValues')
            ->with($tenantId, ['asset-ex'])
            ->willThrowException(DepreciationCoordinationException::assetNotFound($tenantId, 'asset-ex'));

        $request = new DepreciationRunRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            assetIds: ['asset-ex'],
            postToGL: false,
            validateOnly: false,
        );

        // Act & Assert
        $this->expectException(DepreciationCoordinationException::class);
        
        $this->service->executeRun($request);
    }
}
