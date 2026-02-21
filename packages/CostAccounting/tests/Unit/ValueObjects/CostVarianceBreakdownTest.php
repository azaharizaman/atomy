<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\ValueObjects;

use Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CostVarianceBreakdown Value Object
 * 
 * @covers \Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown
 */
final class CostVarianceBreakdownTest extends TestCase
{
    public function testCreateWithAllValues(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 100.00,
            rateVariance: 50.00,
            efficiencyVariance: 25.00,
            totalVariance: 175.00,
            variancePercentage: 17.5,
            materialVariance: 75.00,
            laborVariance: 60.00,
            overheadVariance: 40.00,
            baselineCost: 1000.00
        );
        
        self::assertSame('product_1', $breakdown->getProductId());
        self::assertSame('period_1', $breakdown->getPeriodId());
        self::assertSame(100.00, $breakdown->getPriceVariance());
        self::assertSame(50.00, $breakdown->getRateVariance());
        self::assertSame(25.00, $breakdown->getEfficiencyVariance());
        self::assertSame(175.00, $breakdown->getTotalVariance());
        self::assertSame(17.5, $breakdown->getVariancePercentage());
        self::assertSame(1000.00, $breakdown->getBaselineCost());
        self::assertSame(75.00, $breakdown->getMaterialVariance());
        self::assertSame(60.00, $breakdown->getLaborVariance());
        self::assertSame(40.00, $breakdown->getOverheadVariance());
    }

    public function testCreateWithZeroVariances(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 0.0,
            rateVariance: 0.0,
            efficiencyVariance: 0.0,
            totalVariance: 0.0,
            variancePercentage: 0.0,
            materialVariance: 0.0,
            laborVariance: 0.0,
            overheadVariance: 0.0,
            baselineCost: 0.0
        );
        
        self::assertSame(0.0, $breakdown->getTotalVariance());
        self::assertSame(0.0, $breakdown->getVariancePercentage());
        self::assertSame(0.0, $breakdown->getBaselineCost());
    }

    public function testIsFavorableWithNegativeVariance(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: -100.00,
            rateVariance: -50.00,
            efficiencyVariance: -25.00,
            totalVariance: -175.00,
            variancePercentage: -17.5,
            materialVariance: -75.00,
            laborVariance: -60.00,
            overheadVariance: -40.00,
            baselineCost: 1000.00
        );
        
        self::assertTrue($breakdown->isFavorable());
        self::assertFalse($breakdown->isUnfavorable());
    }

    public function testIsUnfavorableWithPositiveVariance(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 100.00,
            rateVariance: 50.00,
            efficiencyVariance: 25.00,
            totalVariance: 175.00,
            variancePercentage: 17.5,
            materialVariance: 75.00,
            laborVariance: 60.00,
            overheadVariance: 40.00,
            baselineCost: 1000.00
        );
        
        self::assertTrue($breakdown->isUnfavorable());
        self::assertFalse($breakdown->isFavorable());
    }

    public function testIsNotFavorableWithZeroVariance(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 0.0,
            rateVariance: 0.0,
            efficiencyVariance: 0.0,
            totalVariance: 0.0,
            variancePercentage: 0.0,
            materialVariance: 0.0,
            laborVariance: 0.0,
            overheadVariance: 0.0,
            baselineCost: 0.0
        );
        
        self::assertFalse($breakdown->isFavorable());
        self::assertFalse($breakdown->isUnfavorable());
    }

    public function testGetBreakdown(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: 100.00,
            rateVariance: 50.00,
            efficiencyVariance: 25.00,
            totalVariance: 175.00,
            variancePercentage: 17.5,
            materialVariance: 75.00,
            laborVariance: 60.00,
            overheadVariance: 40.00,
            baselineCost: 1000.00
        );
        
        $result = $breakdown->getBreakdown();
        
        self::assertArrayHasKey('price', $result);
        self::assertArrayHasKey('rate', $result);
        self::assertArrayHasKey('efficiency', $result);
        self::assertArrayHasKey('material', $result);
        self::assertArrayHasKey('labor', $result);
        self::assertArrayHasKey('overhead', $result);
        self::assertArrayHasKey('total', $result);
        self::assertArrayHasKey('percentage', $result);
        self::assertArrayHasKey('baseline', $result);
        
        self::assertSame(100.00, $result['price']);
        self::assertSame(50.00, $result['rate']);
        self::assertSame(25.00, $result['efficiency']);
        self::assertSame(75.00, $result['material']);
        self::assertSame(60.00, $result['labor']);
        self::assertSame(40.00, $result['overhead']);
        self::assertSame(175.00, $result['total']);
        self::assertSame(17.5, $result['percentage']);
        self::assertSame(1000.00, $result['baseline']);
    }

    public function testGetBreakdownWithNegativeValues(): void
    {
        $breakdown = new CostVarianceBreakdown(
            productId: 'product_1',
            periodId: 'period_1',
            priceVariance: -50.00,
            rateVariance: -25.00,
            efficiencyVariance: -10.00,
            totalVariance: -85.00,
            variancePercentage: -8.5,
            materialVariance: -40.00,
            laborVariance: -30.00,
            overheadVariance: -15.00,
            baselineCost: 1000.00
        );
        
        $result = $breakdown->getBreakdown();
        
        self::assertSame(-50.00, $result['price']);
        self::assertSame(-25.00, $result['rate']);
        self::assertSame(-10.00, $result['efficiency']);
        self::assertSame(-85.00, $result['total']);
        self::assertSame(-8.5, $result['percentage']);
        self::assertSame(1000.00, $result['baseline']);
    }
}
