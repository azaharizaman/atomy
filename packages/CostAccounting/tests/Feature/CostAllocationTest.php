<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Feature;

use Nexus\CostAccounting\Contracts\CostAllocationEngineInterface;
use Nexus\CostAccounting\Contracts\CostCenterManagerInterface;
use Nexus\CostAccounting\Contracts\CostCenterQueryInterface;
use Nexus\CostAccounting\Contracts\CostPoolPersistInterface;
use Nexus\CostAccounting\Contracts\CostPoolQueryInterface;
use Nexus\CostAccounting\Contracts\ProductCostCalculatorInterface;
use Nexus\CostAccounting\Entities\CostAllocationRule;
use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Enums\AllocationMethod;
use Nexus\CostAccounting\Enums\CostCenterStatus;
use Nexus\CostAccounting\Services\CostAccountingManager;
use Nexus\CostAccounting\Services\CostVarianceCalculator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Integration tests for Cost Allocation functionality
 * 
 * Tests the complete cost allocation workflow including:
 * - Creating cost pools
 * - Setting up allocation rules
 * - Executing cost allocation
 * - Handling edge cases
 * 
 * @coversNothing
 */
final class CostAllocationTest extends TestCase
{
    private CostAccountingManager $manager;
    private $mockCostCenterManager;
    private $mockCostCenterQuery;
    private $mockCostPoolQuery;
    private $mockCostPoolPersist;
    private $mockCostAllocationEngine;
    private $mockProductCostCalculator;
    private $mockVarianceCalculator;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockCostCenterManager = $this->createMock(CostCenterManagerInterface::class);
        $this->mockCostCenterQuery = $this->createMock(CostCenterQueryInterface::class);
        $this->mockCostPoolQuery = $this->createMock(CostPoolQueryInterface::class);
        $this->mockCostPoolPersist = $this->createMock(CostPoolPersistInterface::class);
        $this->mockCostAllocationEngine = $this->createMock(CostAllocationEngineInterface::class);
        $this->mockProductCostCalculator = $this->createMock(ProductCostCalculatorInterface::class);
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

    /**
     * Test scenario: Allocate costs from a cost pool to multiple cost centers
     * using direct allocation method
     */
    public function testDirectAllocationToMultipleCostCenters(): void
    {
        $pool = $this->createPoolWithRules([
            'cc_prod_1' => 0.6,
            'cc_prod_2' => 0.4,
        ]);

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->with('pool_overhead')
            ->willReturn($pool);

        $allocationResult = [
            'allocations' => [
                'cc_prod_1' => 6000.00,
                'cc_prod_2' => 4000.00,
            ],
            'total_allocated' => 10000.00,
        ];

        $this->mockCostAllocationEngine
            ->expects(self::once())
            ->method('allocate')
            ->willReturn($allocationResult);

        $this->mockCostPoolPersist
            ->expects(self::once())
            ->method('save');

        $result = $this->manager->allocatePoolCosts('pool_overhead', 'period_2024-01');

        self::assertSame(10000.00, $result['total_allocated']);
        self::assertArrayHasKey('cc_prod_1', $result['allocations']);
        self::assertArrayHasKey('cc_prod_2', $result['allocations']);
        self::assertSame(6000.00, $result['allocations']['cc_prod_1']);
        self::assertSame(4000.00, $result['allocations']['cc_prod_2']);
    }

    /**
     * Test scenario: Allocation fails when pool is inactive
     */
    public function testAllocationFailsForInactivePool(): void
    {
        $pool = new CostPool(
            id: 'pool_inactive',
            code: 'POOL-INACTIVE',
            name: 'Inactive Pool',
            costCenterId: 'cc_1',
            periodId: 'period_1',
            tenantId: 'tenant_1',
            allocationMethod: AllocationMethod::Direct,
            totalAmount: 1000.00,
            status: 'inactive'
        );

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->willReturn($pool);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not active');

        $this->manager->allocatePoolCosts('pool_inactive', 'period_1');
    }

    /**
     * Test scenario: Allocation fails with insufficient pool balance
     */
    public function testAllocationFailsWithInsufficientBalance(): void
    {
        $pool = $this->createPoolWithRules([
            'cc_1' => 0.6,
            'cc_2' => 0.4,
        ]);
        $pool->updateAmount(500.00);

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->willReturn($pool);

        $this->expectException(\Nexus\CostAccounting\Exceptions\InsufficientCostPoolException::class);

        $this->manager->allocatePoolCosts('pool_1', 'period_1');
    }

    /**
     * Test scenario: Step-down allocation for sequential cost centers
     */
    public function testStepDownAllocation(): void
    {
        $pool = $this->createPoolWithRules(
            ['cc_service_1' => 0.5, 'cc_service_2' => 0.5],
            AllocationMethod::StepDown
        );

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->willReturn($pool);

        $this->mockCostAllocationEngine
            ->expects(self::once())
            ->method('allocate')
            ->willReturn([
                'allocations' => ['cc_service_1' => 500.00, 'cc_service_2' => 500.00],
                'total_allocated' => 1000.00,
                'method' => 'step_down',
            ]);

        $result = $this->manager->allocatePoolCosts('pool_service', 'period_1');

        self::assertSame('step_down', $result['method']);
    }

    /**
     * Test scenario: Reciprocal allocation handles mutual dependencies
     */
    public function testReciprocalAllocation(): void
    {
        $pool = $this->createPoolWithRules(
            ['cc_1' => 0.5, 'cc_2' => 0.5],
            AllocationMethod::Reciprocal
        );

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->willReturn($pool);

        $this->mockCostAllocationEngine
            ->expects(self::once())
            ->method('allocate')
            ->willReturn([
                'allocations' => ['cc_1' => 500.00, 'cc_2' => 500.00],
                'total_allocated' => 1000.00,
                'method' => 'reciprocal',
            ]);

        $result = $this->manager->allocatePoolCosts('pool_mutual', 'period_1');

        self::assertSame('reciprocal', $result['method']);
    }

    /**
     * Test scenario: Create a cost pool with valid data
     */
    public function testCreateCostPoolSuccessfully(): void
    {
        $costCenter = new CostCenter(
            id: 'cc_1',
            code: 'CC001',
            name: 'Manufacturing',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $data = [
            'code' => 'POOL-MATERIAL',
            'name' => 'Material Overhead Pool',
            'cost_center_id' => 'cc_1',
            'period_id' => 'period_2024-01',
            'tenant_id' => 'tenant_1',
            'total_amount' => 5000.00,
            'allocation_method' => AllocationMethod::Direct,
        ];

        $this->mockCostCenterQuery
            ->expects(self::once())
            ->method('findById')
            ->with('cc_1')
            ->willReturn($costCenter);

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findByCode')
            ->with('POOL-MATERIAL')
            ->willReturn(null);

        $this->mockCostPoolPersist
            ->expects(self::once())
            ->method('save');

        $result = $this->manager->createCostPool($data);

        self::assertInstanceOf(CostPool::class, $result);
        self::assertSame('POOL-MATERIAL', $result->getCode());
        self::assertSame(5000.00, $result->getTotalAmount());
    }

    /**
     * Test scenario: Create cost pool fails when cost center not found
     */
    public function testCreateCostPoolFailsWhenCostCenterNotFound(): void
    {
        $data = [
            'code' => 'POOL-001',
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

        $this->expectException(\Nexus\CostAccounting\Exceptions\CostCenterNotFoundException::class);

        $this->manager->createCostPool($data);
    }

    /**
     * Test scenario: Full allocation workflow from pool creation to allocation
     */
    public function testFullAllocationWorkflow(): void
    {
        $pool = new CostPool(
            id: 'pool_overhead',
            code: 'POOL-OVERHEAD',
            name: 'Manufacturing Overhead',
            costCenterId: 'cc_mfg',
            periodId: 'period_2024-01',
            tenantId: 'tenant_1',
            allocationMethod: AllocationMethod::Direct,
            totalAmount: 10000.00
        );

        $rule1 = new CostAllocationRule(
            id: 'rule_1',
            costPoolId: 'pool_overhead',
            receivingCostCenterId: 'cc_prod_1',
            allocationRatio: 0.7,
            tenantId: 'tenant_1',
            allocationMethod: AllocationMethod::Direct,
            isActive: true
        );

        $rule2 = new CostAllocationRule(
            id: 'rule_2',
            costPoolId: 'pool_overhead',
            receivingCostCenterId: 'cc_prod_2',
            allocationRatio: 0.3,
            tenantId: 'tenant_1',
            allocationMethod: AllocationMethod::Direct,
            isActive: true
        );

        $pool->addAllocationRule($rule1);
        $pool->addAllocationRule($rule2);

        $this->mockCostPoolQuery
            ->expects(self::once())
            ->method('findById')
            ->willReturn($pool);

        $this->mockCostAllocationEngine
            ->expects(self::once())
            ->method('allocate')
            ->willReturn([
                'allocations' => [
                    'cc_prod_1' => 7000.00,
                    'cc_prod_2' => 3000.00,
                ],
                'total_allocated' => 10000.00,
            ]);

        $this->mockCostPoolPersist
            ->expects(self::once())
            ->method('save');

        $result = $this->manager->allocatePoolCosts('pool_overhead', 'period_2024-01');

        self::assertSame(10000.00, $result['total_allocated']);
        self::assertSame(7000.00, $result['allocations']['cc_prod_1']);
        self::assertSame(3000.00, $result['allocations']['cc_prod_2']);
        self::assertSame(0.7, $result['allocations']['cc_prod_1'] / $result['total_allocated']);
        self::assertSame(0.3, $result['allocations']['cc_prod_2'] / $result['total_allocated']);
    }

    private function createPoolWithRules(array $ratios, AllocationMethod $method = AllocationMethod::Direct): CostPool
    {
        $pool = new CostPool(
            id: 'pool_1',
            code: 'POOL001',
            name: 'Test Pool',
            costCenterId: 'cc_source',
            periodId: 'period_1',
            tenantId: 'tenant_1',
            allocationMethod: $method,
            totalAmount: 1000.00
        );

        foreach ($ratios as $costCenterId => $ratio) {
            $rule = new CostAllocationRule(
                id: 'rule_' . $costCenterId,
                costPoolId: 'pool_1',
                receivingCostCenterId: $costCenterId,
                allocationRatio: $ratio,
                tenantId: 'tenant_1',
                allocationMethod: $method,
                isActive: true
            );
            $pool->addAllocationRule($rule);
        }

        return $pool;
    }
}
