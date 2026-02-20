<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\Services;

use Nexus\CostAccounting\Contracts\CostAllocationEngineInterface;
use Nexus\CostAccounting\Contracts\CostCenterManagerInterface;
use Nexus\CostAccounting\Contracts\CostCenterQueryInterface;
use Nexus\CostAccounting\Contracts\CostPoolPersistInterface;
use Nexus\CostAccounting\Contracts\CostPoolQueryInterface;
use Nexus\CostAccounting\Contracts\ProductCostCalculatorInterface;
use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Enums\AllocationMethod;
use Nexus\CostAccounting\Enums\CostCenterStatus;
use Nexus\CostAccounting\Exceptions\CostCenterNotFoundException;
use Nexus\CostAccounting\Exceptions\CostPoolNotFoundException;
use Nexus\CostAccounting\Services\CostAccountingManager;
use Nexus\CostAccounting\Services\CostVarianceCalculator;
use Nexus\CostAccounting\ValueObjects\CostCenterHierarchy;
use Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown;
use Nexus\CostAccounting\ValueObjects\ProductCostSnapshot;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CostAccountingManager Service
 * 
 * @covers \Nexus\CostAccounting\Services\CostAccountingManager
 */
final class CostAccountingManagerTest extends TestCase
{
    private CostAccountingManager $manager;
    private $mockCostCenterManager;
    private $mockCostCenterQuery;
    private $mockCostPoolQuery;
    private $mockCostPoolPersist;
    private $mockProductCostCalculator;
    private $mockCostAllocationEngine;
    private $mockVarianceCalculator;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockCostCenterManager = $this->createMock(CostCenterManagerInterface::class);
        $this->mockCostCenterQuery = $this->createMock(CostCenterQueryInterface::class);
        $this->mockCostPoolQuery = $this->createMock(CostPoolQueryInterface::class);
        $this->mockCostPoolPersist = $this->createMock(CostPoolPersistInterface::class);
        $this->mockProductCostCalculator = $this->createMock(ProductCostCalculatorInterface::class);
        $this->mockCostAllocationEngine = $this->createMock(CostAllocationEngineInterface::class);
        $this->mockVarianceCalculator = $this->createMock(CostVarianceCalculator::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->manager = new CostAccountingManager(
            $this->mockCostCenterManager,
            $this->mockCostCenterQuery,
            $this->mockCostPoolQuery,
            $this->mockCostPoolPersist,
            $this->mockProductCostCalculator,
            $this->mockCostAllocationEngine,
            $this->mockVarianceCalculator,
            $this->mockLogger
        );
    }

    public function testCreateCostCenter(): void
    {
        $data = [
            'code' => 'CC001',
            'name' => 'Test Cost Center',
            'tenant_id' => 'tenant_1',
        ];

        $expectedCostCenter = new CostCenter(
            id: 'cc_123',
            code: 'CC001',
            name: 'Test Cost Center',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockCostCenterManager
            ->expects(self::once())
            ->method('create')
            ->with($data)
            ->willReturn($expectedCostCenter);

        $result = $this->manager->createCostCenter($data);

        self::assertSame($expectedCostCenter, $result);
    }

    public function testUpdateCostCenter(): void
    {
        $costCenterId = 'cc_123';
        $data = ['name' => 'Updated Name'];

        $expectedCostCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'Updated Name',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockCostCenterManager
            ->expects(self::once())
            ->method('update')
            ->with($costCenterId, $data)
            ->willReturn($expectedCostCenter);

        $result = $this->manager->updateCostCenter($costCenterId, $data);

        self::assertSame($expectedCostCenter, $result);
    }

    public function testGetCostCenterHierarchyWithoutRoot(): void
    {
        $rootCostCenter = new CostCenter(
            id: 'cc_root',
            code: 'CC001',
            name: 'Root',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockCostCenterQuery
            ->expects(self::once())
            ->method('findRootCostCenters')
            ->with('default')
            ->willReturn([$rootCostCenter]);

        $result = $this->manager->getCostCenterHierarchy();

        self::assertInstanceOf(CostCenterHierarchy::class, $result);
    }

    public function testGetCostCenterHierarchyWithRoot(): void
    {
        $rootCostCenter = new CostCenter(
            id: 'cc_root',
            code: 'CC001',
            name: 'Root',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $hierarchy = new CostCenterHierarchy([$rootCostCenter]);

        $this->mockCostCenterQuery
            ->expects(self::once())
            ->method('getHierarchy')
            ->with('cc_root')
            ->willReturn($hierarchy);

        $result = $this->manager->getCostCenterHierarchy('cc_root');

        self::assertSame($hierarchy, $result);
    }

    public function testCreateCostPool(): void
    {
        $data = [
            'code' => 'POOL001',
            'name' => 'Test Pool',
            'cost_center_id' => 'cc_123',
            'period_id' => 'period_1',
            'tenant_id' => 'tenant_1',
            'total_amount' => 1000.00,
        ];

        $costCenter = new CostCenter(
            id: 'cc_123',
            code: 'CC001',
            name: 'Cost Center',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockCostCenterQuery
            ->expects(self::once())
            ->method('findById')
            ->with('cc_123')
            ->willReturn($costCenter);

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findByCode')
            ->with('POOL001')
            ->willReturn(null);

        $this->mockCostPoolPersist
            ->expects(self::once())
            ->method('save');

        $result = $this->manager->createCostPool($data);

        self::assertInstanceOf(CostPool::class, $result);
        self::assertSame('POOL001', $result->getCode());
    }

    public function testCreateCostPoolWithMissingCostCenter(): void
    {
        $data = [
            'code' => 'POOL001',
            'name' => 'Test Pool',
            'cost_center_id' => 'nonexistent',
            'period_id' => 'period_1',
            'tenant_id' => 'tenant_1',
        ];

        $this->mockCostCenterQuery
            ->expects(self::once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(CostCenterNotFoundException::class);

        $this->manager->createCostPool($data);
    }

    public function testCreateCostPoolWithDuplicateCode(): void
    {
        $data = [
            'code' => 'POOL001',
            'name' => 'Test Pool',
            'cost_center_id' => 'cc_123',
            'period_id' => 'period_1',
            'tenant_id' => 'tenant_1',
        ];

        $costCenter = new CostCenter(
            id: 'cc_123',
            code: 'CC001',
            name: 'Cost Center',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockCostCenterQuery
            ->expects(self::once())
            ->method('findById')
            ->with('cc_123')
            ->willReturn($costCenter);

        $existingPool = new CostPool(
            id: 'pool_existing',
            code: 'POOL001',
            name: 'Existing Pool',
            costCenterId: 'cc_123',
            periodId: 'period_1',
            tenantId: 'tenant_1',
            allocationMethod: AllocationMethod::Direct
        );

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findByCode')
            ->with('POOL001')
            ->willReturn($existingPool);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->manager->createCostPool($data);
    }

    public function testAllocatePoolCosts(): void
    {
        $poolId = 'pool_123';
        $periodId = 'period_1';

        $pool = new CostPool(
            id: $poolId,
            code: 'POOL001',
            name: 'Test Pool',
            costCenterId: 'cc_123',
            periodId: $periodId,
            tenantId: 'tenant_1',
            allocationMethod: AllocationMethod::Direct,
            totalAmount: 1000.00
        );

        $allocationResult = [
            'allocations' => ['cc_1' => 500.00, 'cc_2' => 500.00],
            'total_allocated' => 1000.00,
        ];

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->with($poolId)
            ->willReturn($pool);

        $this->mockCostAllocationEngine
            ->expects(self::once())
            ->method('allocate')
            ->with($pool, $periodId)
            ->willReturn($allocationResult);

        $this->mockCostPoolPersist
            ->expects(self::once())
            ->method('save');

        $result = $this->manager->allocatePoolCosts($poolId, $periodId);

        self::assertSame($allocationResult, $result);
    }

    public function testAllocatePoolCostsPoolNotFound(): void
    {
        $poolId = 'nonexistent';
        $periodId = 'period_1';

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->with($poolId)
            ->willReturn(null);

        $this->expectException(CostPoolNotFoundException::class);

        $this->manager->allocatePoolCosts($poolId, $periodId);
    }

    public function testCalculateProductCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $productCost = new ProductCost(
            id: 'pc_123',
            productId: $productId,
            costCenterId: 'cc_123',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 100.00,
            laborCost: 50.00,
            overheadCost: 25.00
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('calculate')
            ->with($productId, $periodId, 'standard')
            ->willReturn($productCost);

        $result = $this->manager->calculateProductCost($productId, $periodId);

        self::assertSame($productCost, $result);
    }

    public function testPerformCostRollup(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $snapshot = new ProductCostSnapshot(
            productId: $productId,
            periodId: $periodId,
            materialCost: 200.00,
            laborCost: 100.00,
            overheadCost: 50.00,
            totalCost: 350.00,
            unitCost: 35.00,
            level: 1,
            capturedAt: new \DateTimeImmutable()
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('rollup')
            ->with($productId, $periodId)
            ->willReturn($snapshot);

        $result = $this->manager->performCostRollup($productId, $periodId);

        self::assertSame($snapshot, $result);
    }

    public function testCalculateVariances(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $variance = new CostVarianceBreakdown(
            productId: $productId,
            periodId: $periodId,
            priceVariance: 100.00,
            rateVariance: 50.00,
            efficiencyVariance: 25.00,
            totalVariance: 175.00,
            materialVariance: 75.00,
            laborVariance: 60.00,
            overheadVariance: 40.00
        );

        $this->mockVarianceCalculator
            ->expects(self::once())
            ->method('calculate')
            ->with($productId, $periodId)
            ->willReturn($variance);

        $result = $this->manager->calculateVariances($productId, $periodId);

        self::assertSame($variance, $result);
    }
}
