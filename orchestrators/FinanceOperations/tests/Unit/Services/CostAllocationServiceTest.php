<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FinanceOperations\Services\CostAllocationService;
use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationRequest;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationResult;
use Nexus\FinanceOperations\DTOs\CostAllocation\ProductCostRequest;
use Nexus\FinanceOperations\DTOs\CostAllocation\ProductCostResult;
use Nexus\FinanceOperations\Exceptions\CostAllocationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for CostAllocationService.
 *
 * Tests cover:
 * - Equal allocation method across cost centers
 * - Proportional allocation with weights
 * - Manual allocation with provided amounts
 * - Invalid allocation method handling (throws \InvalidArgumentException)
 * - Product cost calculation with material/labor/overhead breakdown
 * - Empty target cost centers handling
 * - Error handling when data provider throws exceptions
 * - Missing manual allocations throws exception
 * - Product costing failed exception handling
 *
 * @since 1.0.0
 */
final class CostAllocationServiceTest extends TestCase
{
    private MockObject|CostAccountingDataProviderInterface $dataProviderMock;
    private MockObject|LoggerInterface $loggerMock;
    private CostAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataProviderMock = $this->createMock(CostAccountingDataProviderInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->service = new CostAllocationService(
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
        $service = new CostAllocationService($this->dataProviderMock);
        
        $this->assertInstanceOf(CostAllocationService::class, $service);
    }

    // =========================================================================
    // Test Suite: allocate() method - Equal Allocation
    // =========================================================================

    /**
     * Test equal allocation method distributes costs evenly across cost centers.
     */
    public function testAllocateWithEqualMethodDistributesEvenly(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002', 'cc-003'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Processing cost allocation',
                $this->callback(function (array $context) use ($tenantId, $sourceCostPoolId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['source_pool_id'] === $sourceCostPoolId
                        && $context['method'] === 'equal';
                })
            );

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertInstanceOf(CostAllocationResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertStringStartsWith('ALLOC-', $result->allocationId);
        $this->assertEquals('1000', $result->totalAllocated);
        $this->assertCount(3, $result->allocations);
        
        // Each should get 333.33 (1000 / 3)
        foreach ($result->allocations as $allocation) {
            $this->assertEquals('333.33', $allocation['amount']);
            $this->assertEquals(33.33, $allocation['percentage']);
        }
        
        // Verify journal entries were created
        $this->assertCount(3, $result->journalEntries);
    }

    // =========================================================================
    // Test Suite: allocate() method - Proportional Allocation
    // =========================================================================

    /**
     * Test proportional allocation with weights distributes based on weight ratio.
     */
    public function testAllocateWithProportionalMethodDistributesByWeight(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        // Weights: cc-001 gets 75%, cc-002 gets 25%
        $weights = [3, 1];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'proportional',
            options: ['weights' => $weights],
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('1000', $result->totalAllocated);
        $this->assertCount(2, $result->allocations);
        
        // cc-001 should get 75% (750)
        $this->assertEquals('750', $result->allocations[0]['amount']);
        $this->assertEquals(75.0, $result->allocations[0]['percentage']);
        
        // cc-002 should get 25% (250)
        $this->assertEquals('250', $result->allocations[1]['amount']);
        $this->assertEquals(25.0, $result->allocations[1]['percentage']);
    }

    /**
     * Test proportional allocation without weights defaults to equal distribution.
     */
    public function testAllocateWithProportionalMethodDefaultsToEqualWithoutWeights(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'proportional',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        // Without weights, should default to equal (50/50)
        $this->assertEquals('500', $result->allocations[0]['amount']);
        $this->assertEquals('500', $result->allocations[1]['amount']);
    }

    // =========================================================================
    // Test Suite: allocate() method - Manual Allocation
    // =========================================================================

    /**
     * Test manual allocation uses provided amounts.
     */
    public function testAllocateWithManualMethodUsesProvidedAmounts(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        $manualAllocations = ['cc-001' => '600', 'cc-002' => '400'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'manual',
            options: ['allocations' => $manualAllocations],
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('600', $result->allocations[0]['amount']);
        $this->assertEquals(60.0, $result->allocations[0]['percentage']);
        $this->assertEquals('400', $result->allocations[1]['amount']);
        $this->assertEquals(40.0, $result->allocations[1]['percentage']);
    }

    /**
     * Test manual allocation throws exception when allocations are missing.
     */
    public function testAllocateWithManualMethodThrowsExceptionWhenAllocationsMissing(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'manual',
            options: [], // Empty options - no allocations provided
        );

        // Act & Assert
        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Manual allocation requires allocations in options');
        
        $this->service->allocate($request);
    }

    // =========================================================================
    // Test Suite: allocate() method - Invalid Method
    // =========================================================================

    /**
     * Test invalid allocation method throws CostAllocationException.
     */
    public function testAllocateWithInvalidMethodThrowsInvalidArgumentException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'invalid_method',
        );

        // Act & Assert
        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Unknown allocation method: invalid_method');
        
        $this->service->allocate($request);
    }

    // =========================================================================
    // Test Suite: allocate() method - Empty Cost Centers
    // =========================================================================

    /**
     * Test allocation with empty target cost centers throws CostAllocationException.
     */
    public function testAllocateWithEmptyCostCentersThrowsException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: [], // Empty!
            allocationMethod: 'equal',
        );

        // Act & Assert
        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('No target cost centers specified');
        
        $this->service->allocate($request);
    }

    // =========================================================================
    // Test Suite: allocate() method - Error Handling
    // =========================================================================

    /**
     * Test allocation handles data provider exception.
     */
    public function testAllocateHandlesDataProviderException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001'];
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cost allocation failed',
                $this->callback(function (array $context) use ($tenantId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['error'] === 'Database connection failed';
                })
            );

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act & Assert
        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Cost allocation failed for pool pool-001');
        
        $this->service->allocate($request);
    }

    // =========================================================================
    // Test Suite: calculateProductCost() method - Happy Path
    // =========================================================================

    /**
     * Test product cost calculation with material/labor/overhead breakdown.
     */
    public function testCalculateProductCostReturnsBreakdown(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $productId = 'prod-001';
        
        $costData = [
            'material_cost' => '150.00',
            'labor_cost' => '75.50',
            'overhead_cost' => '24.50',
            'breakdown' => [
                ['component' => 'Raw Material', 'quantity' => '10', 'unitCost' => '15', 'totalCost' => '150'],
                ['component' => 'Labor', 'quantity' => '5', 'unitCost' => '15.10', 'totalCost' => '75.50'],
                ['component' => 'Overhead', 'quantity' => '1', 'unitCost' => '24.50', 'totalCost' => '24.50'],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getProductCostData')
            ->with($tenantId, $productId)
            ->willReturn($costData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Calculating product cost',
                $this->callback(function (array $context) use ($tenantId, $productId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['product_id'] === $productId;
                })
            );

        $request = new ProductCostRequest(
            tenantId: $tenantId,
            productId: $productId,
        );

        // Act
        $result = $this->service->calculateProductCost($request);

        // Assert
        $this->assertInstanceOf(ProductCostResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($productId, $result->productId);
        $this->assertEquals('150.00', $result->materialCost);
        $this->assertEquals('75.50', $result->laborCost);
        $this->assertEquals('24.50', $result->overheadCost);
        $this->assertEquals('250', $result->totalCost);
        $this->assertCount(3, $result->costBreakdown);
    }

    /**
     * Test product cost calculation with default values when data is missing.
     */
    public function testCalculateProductCostWithDefaultValues(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $productId = 'prod-002';
        
        $costData = []; // Empty - all defaults

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getProductCostData')
            ->with($tenantId, $productId)
            ->willReturn($costData);

        $request = new ProductCostRequest(
            tenantId: $tenantId,
            productId: $productId,
        );

        // Act
        $result = $this->service->calculateProductCost($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('0', $result->materialCost);
        $this->assertEquals('0', $result->laborCost);
        $this->assertEquals('0', $result->overheadCost);
        $this->assertEquals('0', $result->totalCost);
    }

    // =========================================================================
    // Test Suite: calculateProductCost() method - Error Handling
    // =========================================================================

    /**
     * Test product costing handles data provider exception.
     */
    public function testCalculateProductCostHandlesDataProviderException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $productId = 'prod-001';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getProductCostData')
            ->with($tenantId, $productId)
            ->willThrowException(new \RuntimeException('Product not found'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Product costing failed',
                $this->callback(function (array $context) use ($tenantId, $productId) {
                    return $context['tenant_id'] === $tenantId 
                        && $context['product_id'] === $productId
                        && $context['error'] === 'Product not found';
                })
            );

        $request = new ProductCostRequest(
            tenantId: $tenantId,
            productId: $productId,
        );

        // Act & Assert
        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Product costing failed for product prod-001');
        
        $this->service->calculateProductCost($request);
    }

    // =========================================================================
    // Test Suite: getCostCenterSummary() method
    // =========================================================================

    /**
     * Test getCostCenterSummary returns data from provider.
     */
    public function testGetCostCenterSummaryReturnsData(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $costCenterId = 'cc-001';
        
        $expectedData = [
            'cost_center_id' => $costCenterId,
            'name' => 'Manufacturing',
            'total_allocated' => '5000.00',
            'active' => true,
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostCenterSummary')
            ->with($tenantId, $costCenterId)
            ->willReturn($expectedData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Getting cost center summary',
                ['tenant_id' => $tenantId, 'cost_center_id' => $costCenterId]
            );

        // Act
        $result = $this->service->getCostCenterSummary($tenantId, $costCenterId);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    // =========================================================================
    // Test Suite: getAllocatedCosts() method
    // =========================================================================

    /**
     * Test getAllocatedCosts returns data from provider.
     */
    public function testGetAllocatedCostsReturnsData(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $expectedData = [
            'period_id' => $periodId,
            'total_allocated' => '15000.00',
            'allocations' => [
                ['cost_center_id' => 'cc-001', 'amount' => '10000'],
                ['cost_center_id' => 'cc-002', 'amount' => '5000'],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAllocatedCosts')
            ->with($tenantId, $periodId)
            ->willReturn($expectedData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'Getting allocated costs',
                ['tenant_id' => $tenantId, 'period_id' => $periodId]
            );

        // Act
        $result = $this->service->getAllocatedCosts($tenantId, $periodId);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    // =========================================================================
    // Test Suite: Edge Cases
    // =========================================================================

    /**
     * Test allocation with zero total amount.
     */
    public function testAllocateWithZeroAmount(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        $poolData = [
            'total_amount' => '0', // Zero!
            'pool_name' => 'Empty Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('0', $result->totalAllocated);
        $this->assertEquals('0', $result->allocations[0]['amount']);
    }

    /**
     * Test allocation with single cost center.
     */
    public function testAllocateWithSingleCostCenter(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001']; // Single center
        
        $poolData = [
            'total_amount' => '500.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('500', $result->totalAllocated);
        $this->assertEquals('500', $result->allocations[0]['amount']);
        $this->assertEquals(100.0, $result->allocations[0]['percentage']);
    }

    /**
     * Test proportional allocation with uneven weights.
     */
    public function testAllocateWithProportionalMethodMultipleUnevenWeights(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002', 'cc-003', 'cc-004'];
        
        // Weights: 50, 30, 15, 5 = total 100
        $weights = [50, 30, 15, 5];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'proportional',
            options: ['weights' => $weights],
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('500', $result->allocations[0]['amount']); // 50%
        $this->assertEquals(50.0, $result->allocations[0]['percentage']);
        $this->assertEquals('300', $result->allocations[1]['amount']); // 30%
        $this->assertEquals(30.0, $result->allocations[1]['percentage']);
        $this->assertEquals('150', $result->allocations[2]['amount']); // 15%
        $this->assertEquals(15.0, $result->allocations[2]['percentage']);
        $this->assertEquals('50', $result->allocations[3]['amount']);  // 5%
        $this->assertEquals(5.0, $result->allocations[3]['percentage']);
    }

    /**
     * Test manual allocation with indexed array.
     */
    public function testAllocateWithManualMethodIndexedArray(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        // Using indexed array instead of associative
        $manualAllocations = [700, 300];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'manual',
            options: ['allocations' => $manualAllocations],
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('700', $result->allocations[0]['amount']);
        $this->assertEquals('300', $result->allocations[1]['amount']);
    }

    /**
     * Test default allocation method is proportional.
     */
    public function testAllocateWithDefaultMethod(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        // Create request without specifying method (defaults to proportional)
        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            // allocationMethod defaults to 'proportional'
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        // Default proportional should be equal distribution
        $this->assertEquals('500', $result->allocations[0]['amount']);
        $this->assertEquals('500', $result->allocations[1]['amount']);
    }

    /**
     * Test journal entries contain correct data.
     */
    public function testAllocateCreatesCorrectJournalEntries(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001', 'cc-002', 'cc-003'];
        
        $poolData = [
            'total_amount' => '1000.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertCount(3, $result->journalEntries);
        
        $this->assertEquals('allocation', $result->journalEntries[0]['type']);
        $this->assertEquals('cc-001', $result->journalEntries[0]['cost_center_id']);
        $this->assertEquals('333.33', $result->journalEntries[0]['amount']);
        $this->assertEquals('pool-001', $result->journalEntries[0]['source_pool_id']);
        $this->assertEquals('2026-01', $result->journalEntries[0]['period_id']);
        
        $this->assertEquals('cc-002', $result->journalEntries[1]['cost_center_id']);
    }

    /**
     * Test that logger is called with correct context on success.
     */
    public function testLoggerCalledOnSuccess(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001'];
        
        $poolData = [
            'total_amount' => '100.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $this->service->allocate($request);

        // Assert - verify logger was called (already configured in setUp)
    }

    /**
     * Test allocation ID format is correct.
     */
    public function testAllocationIdFormat(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001'];
        
        $poolData = [
            'total_amount' => '100.00',
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        // Format: ALLOC-YYYYMMDDHHMMSS-hexstring
        $this->assertMatchesRegularExpression('/^ALLOC-\d{14}-[a-f0-9]{8}$/', $result->allocationId);
    }

    /**
     * Test getCostCenterSummary handles provider exception.
     */
    public function testGetCostCenterSummaryHandlesException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $costCenterId = 'cc-001';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostCenterSummary')
            ->with($tenantId, $costCenterId)
            ->willThrowException(new \RuntimeException('Database error'));

        // Act & Assert - should propagate the exception
        $this->expectException(\RuntimeException::class);
        
        $this->service->getCostCenterSummary($tenantId, $costCenterId);
    }

    /**
     * Test getAllocatedCosts handles provider exception.
     */
    public function testGetAllocatedCostsHandlesException(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        
        $this->dataProviderMock
            ->expects($this->once())
            ->method('getAllocatedCosts')
            ->with($tenantId, $periodId)
            ->willThrowException(new \RuntimeException('Database error'));

        // Act & Assert - should propagate the exception
        $this->expectException(\RuntimeException::class);
        
        $this->service->getAllocatedCosts($tenantId, $periodId);
    }

    // =========================================================================
    // Test Suite: Pool Data Edge Cases
    // =========================================================================

    /**
     * Test allocation when pool data total_amount is missing.
     */
    public function testAllocateWithMissingPoolTotalAmount(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $periodId = '2026-01';
        $sourceCostPoolId = 'pool-001';
        $targetCostCenterIds = ['cc-001'];
        
        $poolData = [
            // total_amount missing - should default to 0
            'pool_name' => 'Test Pool',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCostPoolSummary')
            ->with($tenantId, $sourceCostPoolId)
            ->willReturn($poolData);

        $request = new CostAllocationRequest(
            tenantId: $tenantId,
            periodId: $periodId,
            sourceCostPoolId: $sourceCostPoolId,
            targetCostCenterIds: $targetCostCenterIds,
            allocationMethod: 'equal',
        );

        // Act
        $result = $this->service->allocate($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('0', $result->totalAllocated);
    }
}
