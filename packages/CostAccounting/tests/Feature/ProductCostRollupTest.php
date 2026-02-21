<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Feature;

use Nexus\CostAccounting\Contracts\CostAllocationEngineInterface;
use Nexus\CostAccounting\Contracts\CostCenterManagerInterface;
use Nexus\CostAccounting\Contracts\CostCenterQueryInterface;
use Nexus\CostAccounting\Contracts\CostPoolPersistInterface;
use Nexus\CostAccounting\Contracts\CostPoolQueryInterface;
use Nexus\CostAccounting\Contracts\ProductCostCalculatorInterface;
use Nexus\CostAccounting\Contracts\CostVarianceCalculatorInterface;
use Nexus\CostAccounting\Contracts\Integration\TenantContextInterface;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Enums\CostType;
use Nexus\CostAccounting\Services\CostAccountingManager;
use Nexus\CostAccounting\Services\CostVarianceCalculator;
use Nexus\CostAccounting\ValueObjects\ProductCostSnapshot;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Integration tests for Product Cost Rollup functionality
 * 
 * Tests the complete product cost rollup workflow including:
 * - Calculating standard costs
 * - Calculating actual costs
 * - Performing multi-level cost rollup
 * - Testing edge cases
 * 
 * @coversNothing
 */
final class ProductCostRollupTest extends TestCase
{
    private CostAccountingManager $manager;
    private $mockCostCenterManager;
    private $mockCostCenterQuery;
    private $mockCostPoolQuery;
    private $mockCostPoolPersist;
    private $mockCostAllocationEngine;
    private $mockProductCostCalculator;
    private $mockVarianceCalculator;
    private $mockTenantContext;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockCostCenterManager = $this->createMock(CostCenterManagerInterface::class);
        $this->mockCostCenterQuery = $this->createMock(CostCenterQueryInterface::class);
        $this->mockCostPoolQuery = $this->createMock(CostPoolQueryInterface::class);
        $this->mockCostPoolPersist = $this->createMock(CostPoolPersistInterface::class);
        $this->mockCostAllocationEngine = $this->createMock(CostAllocationEngineInterface::class);
        $this->mockProductCostCalculator = $this->createMock(ProductCostCalculatorInterface::class);
        $this->mockVarianceCalculator = $this->createMock(CostVarianceCalculatorInterface::class);
        $this->mockTenantContext = $this->createMock(TenantContextInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->manager = new CostAccountingManager(
            $this->mockCostCenterManager,
            $this->mockCostCenterQuery,
            $this->mockCostPoolQuery,
            $this->mockCostPoolPersist,
            $this->mockProductCostCalculator,
            $this->mockCostAllocationEngine,
            $this->mockVarianceCalculator,
            $this->mockTenantContext,
            $this->mockLogger
        );
    }

    /**
     * Test scenario: Calculate standard cost for a product
     */
    public function testCalculateStandardCost(): void
    {
        $productId = 'product_widget';
        $periodId = 'period_2024-01';

        $productCost = new ProductCost(
            id: 'pc_1',
            productId: $productId,
            costCenterId: 'cc_manufacturing',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: CostType::Standard,
            currency: 'USD',
            materialCost: 50.00,
            laborCost: 25.00,
            overheadCost: 15.00
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('calculate')
            ->with($productId, $periodId, CostType::Standard)
            ->willReturn($productCost);

        $result = $this->manager->calculateProductCost($productId, $periodId, CostType::Standard);

        self::assertInstanceOf(ProductCost::class, $result);
        self::assertSame(CostType::Standard, $result->getCostType());
        self::assertSame(50.00, $result->getMaterialCost());
        self::assertSame(25.00, $result->getLaborCost());
        self::assertSame(15.00, $result->getOverheadCost());
        self::assertSame(90.00, $result->getTotalCost());
    }

    /**
     * Test scenario: Calculate actual cost for a product
     */
    public function testCalculateActualCost(): void
    {
        $productId = 'product_widget';
        $periodId = 'period_2024-01';

        $productCost = new ProductCost(
            id: 'pc_1',
            productId: $productId,
            costCenterId: 'cc_manufacturing',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: CostType::Actual,
            currency: 'USD',
            materialCost: 55.00,
            laborCost: 28.00,
            overheadCost: 17.00
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('calculate')
            ->with($productId, $periodId, CostType::Actual)
            ->willReturn($productCost);

        $result = $this->manager->calculateProductCost($productId, $periodId, CostType::Actual);

        self::assertInstanceOf(ProductCost::class, $result);
        self::assertSame(CostType::Actual, $result->getCostType());
        self::assertSame(55.00, $result->getMaterialCost());
    }

    /**
     * Test scenario: Perform cost rollup for a finished product
     */
    public function testPerformCostRollupForFinishedProduct(): void
    {
        $productId = 'product_assembly';
        $periodId = 'period_2024-01';

        $snapshot = new ProductCostSnapshot(
            productId: $productId,
            periodId: $periodId,
            materialCost: 200.00,
            laborCost: 100.00,
            overheadCost: 50.00,
            totalCost: 350.00,
            unitCost: 35.00,
            level: 2,
            capturedAt: new \DateTimeImmutable()
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('rollup')
            ->with($productId, $periodId)
            ->willReturn($snapshot);

        $result = $this->manager->performCostRollup($productId, $periodId);

        self::assertInstanceOf(ProductCostSnapshot::class, $result);
        self::assertSame(350.00, $result->getTotalCost());
        self::assertSame(35.00, $result->getUnitCost());
        self::assertSame(2, $result->getLevel());
    }

    /**
     * Test scenario: Calculate variance between standard and actual costs
     */
    public function testCalculateVariances(): void
    {
        $productId = 'product_widget';
        $periodId = 'period_2024-01';

        $this->mockVarianceCalculator
            ->expects(self::once())
            ->method('calculate')
            ->with($productId, $periodId)
            ->willReturn(new \Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown(
                productId: $productId,
                periodId: $periodId,
                priceVariance: 10.00,
                rateVariance: 5.00,
                efficiencyVariance: 3.00,
                totalVariance: 18.00,
                materialVariance: 8.00,
                laborVariance: 6.00,
                overheadVariance: 4.00
            ));

        $result = $this->manager->calculateVariances($productId, $periodId);

        self::assertInstanceOf(\Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown::class, $result);
        self::assertSame(18.00, $result->getTotalVariance());
    }

    /**
     * Test scenario: Full product cost calculation workflow
     */
    public function testFullProductCostWorkflow(): void
    {
        $productId = 'product_finished';
        $periodId = 'period_2024-01';

        // Step 1: Calculate standard cost
        $standardCost = new ProductCost(
            id: 'pc_std',
            productId: $productId,
            costCenterId: 'cc_mfg',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: CostType::Standard,
            currency: 'USD',
            materialCost: 100.00,
            laborCost: 50.00,
            overheadCost: 25.00
        );

        // Step 2: Calculate actual cost
        $actualCost = new ProductCost(
            id: 'pc_act',
            productId: $productId,
            costCenterId: 'cc_mfg',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: CostType::Actual,
            currency: 'USD',
            materialCost: 110.00,
            laborCost: 55.00,
            overheadCost: 30.00
        );

        // Set up mock to return standard and actual costs on consecutive calls
        $this->mockProductCostCalculator
            ->expects(self::exactly(2))
            ->method('calculate')
            ->willReturnOnConsecutiveCalls($standardCost, $actualCost);

        $result1 = $this->manager->calculateProductCost($productId, $periodId, CostType::Standard);
        self::assertSame(175.00, $result1->getTotalCost());

        $result2 = $this->manager->calculateProductCost($productId, $periodId, CostType::Actual);
        self::assertSame(195.00, $result2->getTotalCost());

        // Step 3: Perform rollup
        $rollupSnapshot = new ProductCostSnapshot(
            productId: $productId,
            periodId: $periodId,
            materialCost: 250.00,
            laborCost: 125.00,
            overheadCost: 75.00,
            totalCost: 450.00,
            unitCost: 45.00,
            level: 1,
            capturedAt: new \DateTimeImmutable()
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('rollup')
            ->with($productId, $periodId)
            ->willReturn($rollupSnapshot);

        $result3 = $this->manager->performCostRollup($productId, $periodId);
        self::assertSame(450.00, $result3->getTotalCost());

        // Step 4: Calculate variances
        $this->mockVarianceCalculator
            ->expects(self::once())
            ->method('calculate')
            ->with($productId, $periodId)
            ->willReturn(new \Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown(
                productId: $productId,
                periodId: $periodId,
                priceVariance: 20.00,
                rateVariance: 10.00,
                efficiencyVariance: 5.00,
                totalVariance: 35.00,
                variancePercentage: 10.0,
                materialVariance: 15.00,
                laborVariance: 12.00,
                overheadVariance: 8.00,
                baselineCost: 350.00
            ));

        $result4 = $this->manager->calculateVariances($productId, $periodId);
        self::assertSame(35.00, $result4->getTotalVariance());
    }

    /**
     * Test scenario: Cost rollup with multi-level BOM
     */
    public function testCostRollupWithMultiLevelBOM(): void
    {
        $productId = 'product_top_level';
        $periodId = 'period_2024-01';

        // Simulate a multi-level rollup
        $snapshot = new ProductCostSnapshot(
            productId: $productId,
            periodId: $periodId,
            materialCost: 500.00,
            laborCost: 200.00,
            overheadCost: 100.00,
            totalCost: 800.00,
            unitCost: 80.00,
            level: 3,
            capturedAt: new \DateTimeImmutable()
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('rollup')
            ->with($productId, $periodId)
            ->willReturn($snapshot);

        $result = $this->manager->performCostRollup($productId, $periodId);

        self::assertInstanceOf(ProductCostSnapshot::class, $result);
        self::assertSame(3, $result->getLevel());
        self::assertSame(800.00, $result->getTotalCost());

        // Verify material cost is the largest component
        self::assertTrue($result->getMaterialCost() > $result->getLaborCost());
        self::assertTrue($result->getMaterialCost() > $result->getOverheadCost());
    }

    /**
     * Test scenario: Unit cost calculation
     */
    public function testUnitCostCalculation(): void
    {
        $productId = 'product_batch';
        $periodId = 'period_2024-01';

        $productCost = new ProductCost(
            id: 'pc_1',
            productId: $productId,
            costCenterId: 'cc_mfg',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: CostType::Standard,
            currency: 'USD',
            materialCost: 1000.00,
            laborCost: 500.00,
            overheadCost: 250.00
        );

        $this->mockProductCostCalculator
            ->expects(self::once())
            ->method('calculate')
            ->willReturn($productCost);

        $result = $this->manager->calculateProductCost($productId, $periodId);

        // Total cost is 1750 for a batch
        self::assertSame(1750.00, $result->getTotalCost());
    }
}
