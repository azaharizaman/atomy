<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\Services;

use Nexus\CostAccounting\Contracts\Integration\InventoryDataProviderInterface;
use Nexus\CostAccounting\Contracts\Integration\ManufacturingDataProviderInterface;
use Nexus\CostAccounting\Contracts\ProductCostPersistInterface;
use Nexus\CostAccounting\Contracts\ProductCostQueryInterface;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Services\ProductCostCalculator;
use Nexus\CostAccounting\ValueObjects\ProductCostSnapshot;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ProductCostCalculator Service
 * 
 * @covers \Nexus\CostAccounting\Services\ProductCostCalculator
 */
final class ProductCostCalculatorTest extends TestCase
{
    private ProductCostCalculator $calculator;
    private $mockQuery;
    private $mockPersist;
    private $mockInventoryProvider;
    private $mockManufacturingProvider;
    private $mockEventDispatcher;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockQuery = $this->createMock(ProductCostQueryInterface::class);
        $this->mockPersist = $this->createMock(ProductCostPersistInterface::class);
        $this->mockInventoryProvider = $this->createMock(InventoryDataProviderInterface::class);
        $this->mockManufacturingProvider = $this->createMock(ManufacturingDataProviderInterface::class);
        $this->mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->calculator = new ProductCostCalculator(
            $this->mockQuery,
            $this->mockPersist,
            $this->mockInventoryProvider,
            $this->mockManufacturingProvider,
            $this->mockEventDispatcher,
            $this->mockLogger
        );
    }

    public function testCalculateStandardCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $this->mockInventoryProvider
            ->expects(self::once())
            ->method('getStandardCost')
            ->with($productId, $periodId)
            ->willReturn(100.00);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getStandardLaborCost')
            ->with($productId, $periodId)
            ->willReturn(50.00);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getOverheadRate')
            ->with($productId, $periodId)
            ->willReturn(0.2);

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $result = $this->calculator->calculate($productId, $periodId, 'standard');

        self::assertInstanceOf(ProductCost::class, $result);
        self::assertSame(100.00, $result->getMaterialCost());
        self::assertSame(50.00, $result->getLaborCost());
        self::assertSame(30.00, $result->getOverheadCost()); // (100+50)*0.2
        self::assertSame('standard', $result->getCostType());
    }

    public function testCalculateActualCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $this->mockInventoryProvider
            ->expects(self::once())
            ->method('getActualCost')
            ->with($productId, $periodId)
            ->willReturn(110.00);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getActualLaborCost')
            ->with($productId, $periodId)
            ->willReturn(55.00);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getActualOverheadCost')
            ->with($productId, $periodId)
            ->willReturn(35.00);

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $result = $this->calculator->calculate($productId, $periodId, 'actual');

        self::assertInstanceOf(ProductCost::class, $result);
        self::assertSame('actual', $result->getCostType());
    }

    public function testCalculateStandardCostUpdatesExisting(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $existingCost = new ProductCost(
            id: 'pc_123',
            productId: $productId,
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 80.00,
            laborCost: 40.00,
            overheadCost: 20.00
        );

        $this->mockInventoryProvider
            ->expects(self::once())
            ->method('getStandardCost')
            ->willReturn(100.00);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getStandardLaborCost')
            ->willReturn(50.00);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getOverheadRate')
            ->willReturn(0.2);

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->willReturn($existingCost);

        $this->mockPersist
            ->expects(self::once())
            ->method('update');

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $result = $this->calculator->calculate($productId, $periodId, 'standard');

        self::assertSame(100.00, $result->getMaterialCost());
        self::assertSame(50.00, $result->getLaborCost());
    }

    public function testRollup(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $productCost = new ProductCost(
            id: 'pc_123',
            productId: $productId,
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 100.00,
            laborCost: 50.00,
            overheadCost: 25.00
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->with($productId, $periodId)
            ->willReturn($productCost);

        $this->mockManufacturingProvider
            ->expects(self::once())
            ->method('getBillOfMaterials')
            ->with($productId)
            ->willReturn([
                ['product_id' => 'comp_1', 'quantity' => 2],
                ['product_id' => 'comp_2', 'quantity' => 3],
            ]);

        $componentCost1 = new ProductCost(
            id: 'pc_comp1',
            productId: 'comp_1',
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 10.00,
            laborCost: 5.00,
            overheadCost: 2.00
        );

        $componentCost2 = new ProductCost(
            id: 'pc_comp2',
            productId: 'comp_2',
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 20.00,
            laborCost: 8.00,
            overheadCost: 4.00
        );

        $this->mockQuery
            ->expects(self::exactly(2))
            ->method('findByProduct')
            ->willReturnMap([
                ['comp_1', $periodId, $componentCost1],
                ['comp_2', $periodId, $componentCost2],
            ]);

        $result = $this->calculator->rollup($productId, $periodId);

        self::assertInstanceOf(ProductCostSnapshot::class, $result);
        
        // Base: 100 + 50 + 25 = 175
        // Components: (10+5+2)*2 + (20+8+4)*3 = 34 + 96 = 130
        // Total: 175 + 130 = 305
        self::assertSame(305.00, $result->getTotalCost());
    }

    public function testRollupThrowsWhenNoProductCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product cost not found');

        $this->calculator->rollup($productId, $periodId);
    }

    public function testCalculateUnitCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';
        $quantity = 10.0;

        $productCost = new ProductCost(
            id: 'pc_123',
            productId: $productId,
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 100.00,
            laborCost: 50.00,
            overheadCost: 25.00
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->with($productId, $periodId)
            ->willReturn($productCost);

        $this->mockPersist
            ->expects(self::once())
            ->method('update');

        $result = $this->calculator->calculateUnitCost($productId, $periodId, $quantity);

        self::assertSame(17.50, $result); // 175 / 10
    }

    public function testCalculateUnitCostThrowsForZeroQuantity(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $productCost = new ProductCost(
            id: 'pc_123',
            productId: $productId,
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'standard',
            currency: 'USD',
            materialCost: 100.00,
            laborCost: 50.00,
            overheadCost: 25.00
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->willReturn($productCost);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be greater than zero');

        $this->calculator->calculateUnitCost($productId, $periodId, 0);
    }

    public function testCalculateUnitCostThrowsWhenNoProductCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $this->mockQuery
            ->expects(self::once())
            ->method('findByProduct')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->calculator->calculateUnitCost($productId, $periodId, 10.0);
    }
}
