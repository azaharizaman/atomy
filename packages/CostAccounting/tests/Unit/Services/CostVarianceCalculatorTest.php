<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\Services;

use Nexus\CostAccounting\Contracts\ProductCostQueryInterface;
use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Events\CostVarianceDetectedEvent;
use Nexus\CostAccounting\Services\CostVarianceCalculator;
use Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CostVarianceCalculator Service
 * 
 * @covers \Nexus\CostAccounting\Services\CostVarianceCalculator
 */
final class CostVarianceCalculatorTest extends TestCase
{
    private CostVarianceCalculator $calculator;
    private $mockProductCostQuery;
    private $mockEventDispatcher;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockProductCostQuery = $this->createMock(ProductCostQueryInterface::class);
        $this->mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->calculator = new CostVarianceCalculator(
            $this->mockProductCostQuery,
            $this->mockEventDispatcher,
            $this->mockLogger
        );
    }

    public function testCalculateWithBothCosts(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $standardCost = new ProductCost(
            id: 'pc_std',
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

        $actualCost = new ProductCost(
            id: 'pc_act',
            productId: $productId,
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'actual',
            currency: 'USD',
            materialCost: 120.00,
            laborCost: 55.00,
            overheadCost: 30.00
        );

        $this->mockProductCostQuery
            ->expects(self::exactly(3))
            ->method('findStandardCost')
            ->with($productId, $periodId)
            ->willReturn($standardCost);

        $this->mockProductCostQuery
            ->expects(self::exactly(3))
            ->method('findActualCost')
            ->with($productId, $periodId)
            ->willReturn($actualCost);

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CostVarianceDetectedEvent::class));

        $result = $this->calculator->calculate($productId, $periodId);

        self::assertInstanceOf(CostVarianceBreakdown::class, $result);
        self::assertSame($productId, $result->getProductId());
        self::assertSame($periodId, $result->getPeriodId());
        
        // Material: 120 - 100 = 20
        // Labor: 55 - 50 = 5
        // Overhead: 30 - 25 = 5
        // Total: 30
        self::assertSame(20.00, $result->getMaterialVariance());
        self::assertSame(5.00, $result->getLaborVariance());
        self::assertSame(5.00, $result->getOverheadVariance());
        self::assertSame(30.00, $result->getTotalVariance());
    }

    public function testCalculateWithOnlyStandardCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $standardCost = new ProductCost(
            id: 'pc_std',
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

        $this->mockProductCostQuery
            ->expects(self::once())
            ->method('findStandardCost')
            ->with($productId, $periodId)
            ->willReturn($standardCost);

        $this->mockProductCostQuery
            ->expects(self::once())
            ->method('findActualCost')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CostVarianceDetectedEvent::class));

        $result = $this->calculator->calculate($productId, $periodId);

        self::assertInstanceOf(CostVarianceBreakdown::class, $result);
        
        // All actuals default to 0
        // Material: 0 - 100 = -100
        // Labor: 0 - 50 = -50
        // Overhead: 0 - 25 = -25
        // Total: -175 (favorable)
        self::assertSame(-100.00, $result->getMaterialVariance());
        self::assertTrue($result->isFavorable());
    }

    public function testCalculateWithOnlyActualCost(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $actualCost = new ProductCost(
            id: 'pc_act',
            productId: $productId,
            costCenterId: 'cc_1',
            periodId: $periodId,
            tenantId: 'tenant_1',
            costType: 'actual',
            currency: 'USD',
            materialCost: 120.00,
            laborCost: 55.00,
            overheadCost: 30.00
        );

        $this->mockProductCostQuery
            ->expects(self::once())
            ->method('findStandardCost')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->mockProductCostQuery
            ->expects(self::once())
            ->method('findActualCost')
            ->with($productId, $periodId)
            ->willReturn($actualCost);

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CostVarianceDetectedEvent::class));

        $result = $this->calculator->calculate($productId, $periodId);

        self::assertInstanceOf(CostVarianceBreakdown::class, $result);
        
        // All standards default to 0
        // Material: 120 - 0 = 120
        // Labor: 55 - 0 = 55
        // Overhead: 30 - 0 = 30
        // Total: 205 (unfavorable)
        self::assertSame(205.00, $result->getTotalVariance());
        self::assertTrue($result->isUnfavorable());
    }

    public function testCalculateThrowsWhenNoCosts(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';

        $this->mockProductCostQuery
            ->expects(self::once())
            ->method('findStandardCost')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->mockProductCostQuery
            ->expects(self::once())
            ->method('findActualCost')
            ->with($productId, $periodId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No cost data found');

        $this->calculator->calculate($productId, $periodId);
    }

    public function testExceedsThresholdWithVarianceAboveThreshold(): void
    {
        $variance = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 100.00,
            rateVariance: 50.00,
            efficiencyVariance: 25.00,
            totalVariance: 200.00,
            materialVariance: 75.00,
            laborVariance: 75.00,
            overheadVariance: 50.00
        );

        // 10% of 200 = 20, actual is 200 > 20
        $result = $this->calculator->exceedsThreshold($variance, 10.0);

        self::assertTrue($result);
    }

    public function testExceedsThresholdWithVarianceBelowThreshold(): void
    {
        $variance = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 5.00,
            rateVariance: 2.50,
            efficiencyVariance: 1.25,
            totalVariance: 10.00,
            materialVariance: 3.75,
            laborVariance: 3.75,
            overheadVariance: 2.50
        );

        // 10% of 10 = 1, actual is 10 > 1
        $result = $this->calculator->exceedsThreshold($variance, 50.0);

        self::assertFalse($result);
    }

    public function testExceedsThresholdWithNegativeVariance(): void
    {
        $variance = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: -50.00,
            rateVariance: -25.00,
            efficiencyVariance: -12.50,
            totalVariance: -100.00,
            materialVariance: -37.50,
            laborVariance: -37.50,
            overheadVariance: -25.00
        );

        // 10% of |-100| = 10, actual is 100 > 10
        $result = $this->calculator->exceedsThreshold($variance, 10.0);

        self::assertTrue($result);
    }
}
