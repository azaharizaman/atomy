<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FinanceOperations\Services\GLReconciliationService;
use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Nexus\FinanceOperations\DTOs\GLPosting\GLReconciliationRequest;
use Nexus\FinanceOperations\DTOs\GLPosting\GLReconciliationResult;
use Nexus\FinanceOperations\DTOs\GLPosting\ConsistencyCheckRequest;
use Nexus\FinanceOperations\DTOs\GLPosting\ConsistencyCheckResult;
use Nexus\FinanceOperations\Exceptions\GLReconciliationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for GLReconciliationService.
 *
 * Tests cover:
 * - Successful reconciliation (subledger matches GL)
 * - Reconciliation with variance detection
 * - Auto-adjustment mode for discrepancies
 * - Consistency check across multiple subledger types
 * - Consistency check with inconsistencies
 * - Control account mapping for different subledger types (AR, AP, FA, INV)
 * - Error handling when data provider throws exceptions
 * - Reconciliation mismatch exception handling
 *
 * @since 1.0.0
 */
final class GLReconciliationServiceTest extends TestCase
{
    private MockObject|GLReconciliationProviderInterface $dataProviderMock;
    private MockObject|LoggerInterface $loggerMock;
    private GLReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataProviderMock = $this->createMock(GLReconciliationProviderInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->service = new GLReconciliationService(
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
        $service = new GLReconciliationService($this->dataProviderMock);

        $this->assertInstanceOf(GLReconciliationService::class, $service);
    }

    // =========================================================================
    // Test Suite: reconcile() method - Successful Reconciliation
    // =========================================================================

    /**
     * Test successful reconciliation when subledger matches GL.
     */
    public function testReconcileReturnsSuccessWhenSubledgerMatchesGL(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';
        $balance = '10000.00';

        $subledgerData = ['balance' => $balance];
        $glData = ['balance' => $balance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Starting GL reconciliation',
                $this->callback(function (array $context) use ($tenantId, $periodId, $subledgerType) {
                    return $context['tenant_id'] === $tenantId
                        && $context['period_id'] === $periodId
                        && $context['subledger_type'] === $subledgerType;
                })
            );

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertInstanceOf(GLReconciliationResult::class, $result);
        $this->assertTrue($result->success, 'Reconciliation should succeed when balances match');
        $this->assertEquals($subledgerType, $result->subledgerType);
        $this->assertEquals($balance, $result->subledgerBalance);
        $this->assertEquals($balance, $result->glBalance);
        $this->assertEquals('0', $result->variance);
        $this->assertEmpty($result->discrepancies);
    }

    /**
     * Test reconciliation with small variance within tolerance.
     */
    public function testReconcileTreatsSmallVarianceAsReconciled(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';
        $subledgerBalance = '10000.00';
        $glBalance = '10000.005'; // Within 0.01 tolerance

        $subledgerData = ['balance' => $subledgerBalance];
        $glData = ['balance' => $glBalance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertTrue($result->success, 'Reconciliation should succeed when variance is within tolerance');
    }

    // =========================================================================
    // Test Suite: reconcile() method - Variance Detection
    // =========================================================================

    /**
     * Test reconciliation detects variance between subledger and GL.
     */
    public function testReconcileDetectsVariance(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';
        $subledgerBalance = '10000.00';
        $glBalance = '9500.00';
        $expectedVariance = '500';

        $subledgerData = ['balance' => $subledgerBalance];
        $glData = ['balance' => $glBalance];
        $discrepancyData = [
            'discrepancies' => [
                ['subledger_type' => 'AR', 'type' => 'missing_entry', 'amount' => '500.00'],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDiscrepancies')
            ->with($tenantId, $periodId)
            ->willReturn($discrepancyData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertInstanceOf(GLReconciliationResult::class, $result);
        $this->assertFalse($result->success, 'Reconciliation should fail when variance exists');
        $this->assertEquals($subledgerBalance, $result->subledgerBalance);
        $this->assertEquals($glBalance, $result->glBalance);
        $this->assertEquals($expectedVariance, $result->variance);
        $this->assertNotEmpty($result->discrepancies);
    }

    /**
     * Test reconciliation with variance where GL is higher than subledger.
     */
    public function testReconcileWithGLHigherThanSubledger(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AP';
        $subledgerBalance = '8000.00';
        $glBalance = '8500.00';
        $expectedVariance = '-500'; // Negative variance

        $subledgerData = ['balance' => $subledgerBalance];
        $glData = ['balance' => $glBalance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AP_CONTROL')
            ->willReturn($glData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDiscrepancies')
            ->with($tenantId, $periodId)
            ->willReturn(['discrepancies' => []]);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertFalse($result->success);
        // Variance is converted to string without trailing zeros
        $this->assertEquals($expectedVariance, $result->variance);
    }

    // =========================================================================
    // Test Suite: reconcile() method - Auto-Adjustment
    // =========================================================================

    /**
     * Test auto-adjustment mode creates adjusting entries.
     */
    public function testReconcileWithAutoAdjustCreatesAdjustingEntries(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';
        $subledgerBalance = '10000.00';
        $glBalance = '9500.00';

        $subledgerData = ['balance' => $subledgerBalance];
        $glData = ['balance' => $glBalance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Creating adjusting entries',
                $this->callback(function (array $context) use ($tenantId, $subledgerType) {
                    return $context['tenant_id'] === $tenantId
                        && $context['subledger_type'] === $subledgerType;
                })
            );

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: true, // Enable auto-adjustment
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertInstanceOf(GLReconciliationResult::class, $result);
        $this->assertTrue($result->success, 'Auto-adjustment should succeed');
        $this->assertEquals('0', $result->variance, 'Variance should be zeroed after adjustment');
    }

    // =========================================================================
    // Test Suite: reconcile() method - Control Account Mapping
    // =========================================================================

    /**
     * Test control account mapping for Accounts Receivable (AR).
     */
    public function testReconcileMapsControlAccountForAR(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'receivable';
        $balance = '5000.00';

        $subledgerData = ['balance' => $balance];
        $glData = ['balance' => $balance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertTrue($result->success);
    }

    /**
     * Test control account mapping for Accounts Payable (AP).
     */
    public function testReconcileMapsControlAccountForAP(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'payable';
        $balance = '3000.00';

        $subledgerData = ['balance' => $balance];
        $glData = ['balance' => $balance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AP_CONTROL')
            ->willReturn($glData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertTrue($result->success);
    }

    /**
     * Test control account mapping for Fixed Assets (FA).
     */
    public function testReconcileMapsControlAccountForFA(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'asset';
        $balance = '100000.00';

        $subledgerData = ['balance' => $balance];
        $glData = ['balance' => $balance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'ASSET_CONTROL')
            ->willReturn($glData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertTrue($result->success);
    }

    /**
     * Test control account mapping for Inventory (INV).
     */
    public function testReconcileMapsControlAccountForINV(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'inventory';
        $balance = '75000.00';

        $subledgerData = ['balance' => $balance];
        $glData = ['balance' => $balance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'INVENTORY_CONTROL')
            ->willReturn($glData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertTrue($result->success);
    }

    /**
     * Test control account mapping for custom subledger type.
     */
    public function testReconcileMapsControlAccountForCustomSubledger(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'payroll';
        $balance = '25000.00';

        $subledgerData = ['balance' => $balance];
        $glData = ['balance' => $balance];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'PAYROLL_CONTROL')
            ->willReturn($glData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Test Suite: reconcile() method - Error Handling
    // =========================================================================

    /**
     * Test reconciliation handles data provider exception.
     */
    public function testReconcileHandlesDataProviderException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'GL reconciliation failed',
                $this->callback(function (array $context) use ($tenantId, $subledgerType) {
                    return $context['tenant_id'] === $tenantId
                        && $context['subledger_type'] === $subledgerType
                        && $context['error'] === 'Database connection failed';
                })
            );

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act & Assert
        $this->expectException(GLReconciliationException::class);
        $this->expectExceptionMessage('Reconciliation mismatch for AR');

        $this->service->reconcile($request);
    }

    /**
     * Test reconciliation propagates GLReconciliationException.
     */
    public function testReconcilePropagatesGLReconciliationException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willThrowException(GLReconciliationException::reconciliationMismatch(
                $tenantId,
                $subledgerType,
                '1000',
                '0',
                'Data error'
            ));

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act & Assert
        $this->expectException(GLReconciliationException::class);

        $this->service->reconcile($request);
    }

    /**
     * Test reconciliation handles missing balance data.
     */
    public function testReconcileHandlesMissingBalanceData(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';

        // No balance key in returned data
        $subledgerData = [];
        $glData = [];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        // Note: getDiscrepancies is NOT called when variance is 0 (within tolerance)
        // Since empty arrays default to '0' balance, variance is 0 and no discrepancy check is needed

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertInstanceOf(GLReconciliationResult::class, $result);
        // When both are empty/zero, variance is '0' and getDiscrepancies is NOT called
        $this->assertEquals('0', $result->subledgerBalance);
        $this->assertEquals('0', $result->glBalance);
        $this->assertEquals('0', $result->variance);
    }

    // =========================================================================
    // Test Suite: checkConsistency() method - Happy Path
    // =========================================================================

    /**
     * Test consistency check returns success when all subledgers are consistent.
     */
    public function testCheckConsistencyReturnsSuccessWhenAllConsistent(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerTypes = ['AR', 'AP'];

        $statusData = [
            'details' => [
                'AR' => [
                    'subledger_balance' => '10000.00',
                    'gl_balance' => '10000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
                'AP' => [
                    'subledger_balance' => '5000.00',
                    'gl_balance' => '5000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willReturn($statusData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Checking GL consistency',
                $this->callback(function (array $context) use ($tenantId, $periodId) {
                    return $context['tenant_id'] === $tenantId
                        && $context['period_id'] === $periodId;
                })
            );

        $request = new ConsistencyCheckRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerTypes: $subledgerTypes,
        );

        // Act
        $result = $this->service->checkConsistency($request);

        // Assert
        $this->assertInstanceOf(ConsistencyCheckResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertTrue($result->allConsistent, 'All subledgers should be consistent');
        $this->assertEmpty($result->inconsistencies);
        $this->assertCount(2, $result->checks);
    }

    // =========================================================================
    // Test Suite: checkConsistency() method - With Inconsistencies
    // =========================================================================

    /**
     * Test consistency check detects inconsistencies.
     */
    public function testCheckConsistencyDetectsInconsistencies(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerTypes = ['AR', 'AP'];

        $statusData = [
            'details' => [
                'AR' => [
                    'subledger_balance' => '10000.00',
                    'gl_balance' => '10000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
                'AP' => [
                    'subledger_balance' => '5000.00',
                    'gl_balance' => '4500.00',
                    'variance' => '500.00',
                    'is_reconciled' => false,
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willReturn($statusData);

        $request = new ConsistencyCheckRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerTypes: $subledgerTypes,
        );

        // Act
        $result = $this->service->checkConsistency($request);

        // Assert
        $this->assertInstanceOf(ConsistencyCheckResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertFalse($result->allConsistent, 'Not all subledgers should be consistent');
        $this->assertNotEmpty($result->inconsistencies);
        $this->assertCount(1, $result->inconsistencies);
        $this->assertEquals('AP', $result->inconsistencies[0]['subledgerType']);
    }

    /**
     * Test consistency check with missing subledger data.
     */
    public function testCheckConsistencyHandlesMissingSubledgerData(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerTypes = ['AR', 'AP', 'FA'];

        // Only AR data is present
        $statusData = [
            'details' => [
                'AR' => [
                    'subledger_balance' => '10000.00',
                    'gl_balance' => '10000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willReturn($statusData);

        $request = new ConsistencyCheckRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerTypes: $subledgerTypes,
        );

        // Act
        $result = $this->service->checkConsistency($request);

        // Assert
        $this->assertInstanceOf(ConsistencyCheckResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertFalse($result->allConsistent, 'Should not be consistent when data is missing');

        // Should have entries for all requested types, with missing ones marked inconsistent
        $this->assertArrayHasKey('AR', $result->checks);
        $this->assertArrayHasKey('AP', $result->checks);
        $this->assertArrayHasKey('FA', $result->checks);

        $this->assertTrue($result->checks['AR']['consistent']);
        $this->assertFalse($result->checks['AP']['consistent'], 'AP should be marked inconsistent due to missing data');
        $this->assertFalse($result->checks['FA']['consistent'], 'FA should be marked inconsistent due to missing data');
    }

    // =========================================================================
    // Test Suite: checkConsistency() method - Multiple Subledger Types
    // =========================================================================

    /**
     * Test consistency check across all four subledger types (AR, AP, FA, INV).
     */
    public function testCheckConsistencyAcrossAllSubledgerTypes(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerTypes = ['AR', 'AP', 'FA', 'INV'];

        $statusData = [
            'details' => [
                'AR' => [
                    'subledger_balance' => '10000.00',
                    'gl_balance' => '10000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
                'AP' => [
                    'subledger_balance' => '8000.00',
                    'gl_balance' => '8000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
                'FA' => [
                    'subledger_balance' => '50000.00',
                    'gl_balance' => '50000.00',
                    'variance' => '0',
                    'is_reconciled' => true,
                ],
                'INV' => [
                    'subledger_balance' => '25000.00',
                    'gl_balance' => '24000.00',
                    'variance' => '1000.00',
                    'is_reconciled' => false,
                ],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willReturn($statusData);

        $request = new ConsistencyCheckRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerTypes: $subledgerTypes,
        );

        // Act
        $result = $this->service->checkConsistency($request);

        // Assert
        $this->assertInstanceOf(ConsistencyCheckResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertFalse($result->allConsistent);
        $this->assertCount(4, $result->checks);
        $this->assertCount(1, $result->inconsistencies);
        $this->assertEquals('INV', $result->inconsistencies[0]['subledgerType']);
    }

    // =========================================================================
    // Test Suite: checkConsistency() method - Error Handling
    // =========================================================================

    /**
     * Test consistency check handles provider exception.
     */
    public function testCheckConsistencyHandlesProviderException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerTypes = ['AR'];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willThrowException(new \RuntimeException('Database error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Consistency check failed',
                $this->callback(function (array $context) use ($tenantId) {
                    return $context['tenant_id'] === $tenantId
                        && $context['error'] === 'Database error';
                })
            );

        $request = new ConsistencyCheckRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerTypes: $subledgerTypes,
        );

        // Act
        $result = $this->service->checkConsistency($request);

        // Assert
        $this->assertInstanceOf(ConsistencyCheckResult::class, $result);
        $this->assertFalse($result->success, 'Result should indicate failure');
        $this->assertFalse($result->allConsistent);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Database error', $result->error);
    }

    /**
     * Test consistency check with alternative status format.
     */
    public function testCheckConsistencyWithAlternativeStatusFormat(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerTypes = ['AR'];

        // Alternative format using top-level keys instead of 'details'
        $statusData = [
            'AR' => [
                'subledger_balance' => '5000.00',
                'gl_balance' => '5000.00',
                'variance' => '0',
                'consistent' => true, // Using 'consistent' instead of 'is_reconciled'
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willReturn($statusData);

        $request = new ConsistencyCheckRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerTypes: $subledgerTypes,
        );

        // Act
        $result = $this->service->checkConsistency($request);

        // Assert
        $this->assertInstanceOf(ConsistencyCheckResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertTrue($result->allConsistent);
    }

    // =========================================================================
    // Test Suite: getReconciliationStatus() method
    // =========================================================================

    /**
     * Test getReconciliationStatus returns status data.
     */
    public function testGetReconciliationStatusReturnsStatusData(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $expectedStatus = [
            'AR' => ['is_reconciled' => true, 'variance' => '0'],
            'AP' => ['is_reconciled' => true, 'variance' => '0'],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getReconciliationStatus')
            ->with($tenantId, $periodId)
            ->willReturn($expectedStatus);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Getting reconciliation status',
                $this->callback(function (array $context) use ($tenantId, $periodId) {
                    return $context['tenant_id'] === $tenantId
                        && $context['period_id'] === $periodId;
                })
            );

        // Act
        $result = $this->service->getReconciliationStatus($tenantId, $periodId);

        // Assert
        $this->assertEquals($expectedStatus, $result);
    }

    // =========================================================================
    // Test Suite: Private Helper Methods
    // =========================================================================

    /**
     * Test filterDiscrepanciesByType filters correctly.
     * This is tested indirectly through the reconcile method.
     */
    public function testFilterDiscrepanciesByTypeWithMixedTypes(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $subledgerType = 'AR';
        $subledgerBalance = '10000.00';
        $glBalance = '9500.00';

        $subledgerData = ['balance' => $subledgerBalance];
        $glData = ['balance' => $glBalance];

        // Multiple discrepancy types
        $discrepancyData = [
            'discrepancies' => [
                ['subledger_type' => 'AR', 'type' => 'missing_entry', 'amount' => '300.00'],
                ['subledger_type' => 'AP', 'type' => 'duplicate_entry', 'amount' => '200.00'],
                ['type' => 'AR', 'amount' => '200.00'], // Alternative format
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getSubledgerBalance')
            ->with($tenantId, $periodId, $subledgerType)
            ->willReturn($subledgerData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getGLBalance')
            ->with($tenantId, $periodId, 'AR_CONTROL')
            ->willReturn($glData);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getDiscrepancies')
            ->with($tenantId, $periodId)
            ->willReturn($discrepancyData);

        $request = new GLReconciliationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
            autoAdjust: false,
        );

        // Act
        $result = $this->service->reconcile($request);

        // Assert
        $this->assertFalse($result->success);
        // Should filter to only AR discrepancies
        $this->assertCount(2, $result->discrepancies);
    }
}
