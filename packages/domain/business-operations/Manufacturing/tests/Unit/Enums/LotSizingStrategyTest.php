<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Enums;

use Nexus\Manufacturing\Enums\LotSizingStrategy;
use Nexus\Manufacturing\Tests\TestCase;

final class LotSizingStrategyTest extends TestCase
{
    public function testAllStrategiesExist(): void
    {
        $this->assertCount(5, LotSizingStrategy::cases());

        $this->assertSame('lot_for_lot', LotSizingStrategy::LOT_FOR_LOT->value);
        $this->assertSame('fixed_order_quantity', LotSizingStrategy::FIXED_ORDER_QUANTITY->value);
        $this->assertSame('economic_order_quantity', LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->value);
        $this->assertSame('period_order_quantity', LotSizingStrategy::PERIOD_ORDER_QUANTITY->value);
        $this->assertSame('least_unit_cost', LotSizingStrategy::LEAST_UNIT_COST->value);
    }

    public function testLabel(): void
    {
        $this->assertSame('Lot-for-Lot', LotSizingStrategy::LOT_FOR_LOT->label());
        $this->assertSame('Fixed Order Quantity', LotSizingStrategy::FIXED_ORDER_QUANTITY->label());
        $this->assertSame('Economic Order Quantity (EOQ)', LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->label());
        $this->assertSame('Period Order Quantity', LotSizingStrategy::PERIOD_ORDER_QUANTITY->label());
        $this->assertSame('Least Unit Cost', LotSizingStrategy::LEAST_UNIT_COST->label());
    }

    public function testDescription(): void
    {
        $this->assertStringContainsString('exactly', strtolower(LotSizingStrategy::LOT_FOR_LOT->description()));
        $this->assertStringContainsString('same', strtolower(LotSizingStrategy::FIXED_ORDER_QUANTITY->description()));
        $this->assertStringContainsString('balance', strtolower(LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->description()));
        $this->assertStringContainsString('period', strtolower(LotSizingStrategy::PERIOD_ORDER_QUANTITY->description()));
        $this->assertStringContainsString('cost', strtolower(LotSizingStrategy::LEAST_UNIT_COST->description()));
    }

    public function testRequiresDemandHistory(): void
    {
        $this->assertFalse(LotSizingStrategy::LOT_FOR_LOT->requiresDemandHistory());
        $this->assertFalse(LotSizingStrategy::FIXED_ORDER_QUANTITY->requiresDemandHistory());
        $this->assertTrue(LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->requiresDemandHistory());
        $this->assertFalse(LotSizingStrategy::PERIOD_ORDER_QUANTITY->requiresDemandHistory());
        $this->assertFalse(LotSizingStrategy::LEAST_UNIT_COST->requiresDemandHistory());
    }

    public function testGetRequiredParameters(): void
    {
        $this->assertEmpty(LotSizingStrategy::LOT_FOR_LOT->getRequiredParameters());

        $foqParams = LotSizingStrategy::FIXED_ORDER_QUANTITY->getRequiredParameters();
        $this->assertContains('quantity', $foqParams);

        $eoqParams = LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->getRequiredParameters();
        $this->assertContains('orderingCost', $eoqParams);
        $this->assertContains('holdingCostRate', $eoqParams);
        $this->assertContains('annualDemand', $eoqParams);

        $poqParams = LotSizingStrategy::PERIOD_ORDER_QUANTITY->getRequiredParameters();
        $this->assertContains('periods', $poqParams);

        $lucParams = LotSizingStrategy::LEAST_UNIT_COST->getRequiredParameters();
        $this->assertContains('orderingCost', $lucParams);
        $this->assertContains('holdingCostRate', $lucParams);
    }

    public function testGetDefaultParameters(): void
    {
        $this->assertEmpty(LotSizingStrategy::LOT_FOR_LOT->getDefaultParameters());

        $foqDefaults = LotSizingStrategy::FIXED_ORDER_QUANTITY->getDefaultParameters();
        $this->assertArrayHasKey('quantity', $foqDefaults);
        $this->assertSame(100.0, $foqDefaults['quantity']);

        $eoqDefaults = LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->getDefaultParameters();
        $this->assertArrayHasKey('orderingCost', $eoqDefaults);
        $this->assertArrayHasKey('holdingCostRate', $eoqDefaults);

        $poqDefaults = LotSizingStrategy::PERIOD_ORDER_QUANTITY->getDefaultParameters();
        $this->assertArrayHasKey('periods', $poqDefaults);
        $this->assertSame(4, $poqDefaults['periods']);

        $lucDefaults = LotSizingStrategy::LEAST_UNIT_COST->getDefaultParameters();
        $this->assertArrayHasKey('maxPeriods', $lucDefaults);
    }

    public function testGetBestUseCases(): void
    {
        $lotForLotCases = LotSizingStrategy::LOT_FOR_LOT->getBestUseCases();
        $this->assertIsArray($lotForLotCases);
        $this->assertNotEmpty($lotForLotCases);

        $eoqCases = LotSizingStrategy::ECONOMIC_ORDER_QUANTITY->getBestUseCases();
        $this->assertIsArray($eoqCases);
        $this->assertNotEmpty($eoqCases);
    }
}
