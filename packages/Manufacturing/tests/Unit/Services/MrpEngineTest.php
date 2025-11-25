<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Services;

use Nexus\Manufacturing\Contracts\BomInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\InventoryDataProviderInterface;
use Nexus\Manufacturing\Contracts\DemandDataProviderInterface;
use Nexus\Manufacturing\Enums\LotSizingStrategy;
use Nexus\Manufacturing\Services\MrpEngine;
use Nexus\Manufacturing\Tests\TestCase;
use Nexus\Manufacturing\ValueObjects\BomLine;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use PHPUnit\Framework\MockObject\MockObject;

final class MrpEngineTest extends TestCase
{
    private BomManagerInterface&MockObject $bomManager;
    private InventoryDataProviderInterface&MockObject $inventoryProvider;
    private DemandDataProviderInterface&MockObject $demandProvider;
    private PlanningHorizon $horizon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bomManager = $this->createMock(BomManagerInterface::class);
        $this->inventoryProvider = $this->createMock(InventoryDataProviderInterface::class);
        $this->demandProvider = $this->createMock(DemandDataProviderInterface::class);

        $this->horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
        );
    }

    public function testRunMrpForSingleProduct(): void
    {
        // Setup inventory
        $this->inventoryProvider
            ->method('getOnHandQuantity')
            ->with('prod-001')
            ->willReturn(100.0);

        $this->inventoryProvider
            ->method('getSafetyStock')
            ->with('prod-001')
            ->willReturn(50.0);

        $this->inventoryProvider
            ->method('getScheduledReceipts')
            ->willReturn([]);

        $this->inventoryProvider
            ->method('getLeadTimeDays')
            ->willReturn(7);

        $this->inventoryProvider
            ->method('getReplenishmentType')
            ->willReturn('manufacture');

        // Setup demand
        $this->demandProvider
            ->method('getGrossRequirements')
            ->willReturn([
                '2024-01-08' => 150.0,
                '2024-01-15' => 200.0,
            ]);

        $engine = new MrpEngine(
            $this->bomManager,
            $this->inventoryProvider,
            $this->demandProvider,
        );

        $result = $engine->calculate('prod-001', $this->horizon);

        $this->assertSame('prod-001', $result->productId);
        $this->assertNotEmpty($result->materialRequirements);
    }

    public function testMrpCalculateMultipleProducts(): void
    {
        $this->inventoryProvider
            ->method('getOnHandQuantity')
            ->willReturn(100.0);

        $this->inventoryProvider
            ->method('getSafetyStock')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getScheduledReceipts')
            ->willReturn([]);

        $this->inventoryProvider
            ->method('getLeadTimeDays')
            ->willReturn(7);

        $this->inventoryProvider
            ->method('getReplenishmentType')
            ->willReturn('purchase');

        $this->demandProvider
            ->method('getGrossRequirements')
            ->willReturn(['2024-01-15' => 100.0]);

        $engine = new MrpEngine(
            $this->bomManager,
            $this->inventoryProvider,
            $this->demandProvider,
        );

        $results = $engine->calculateMultiple(['prod-001', 'prod-002'], $this->horizon);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('prod-001', $results);
        $this->assertArrayHasKey('prod-002', $results);
    }

    public function testMrpRegenerateDeletesExistingOrders(): void
    {
        $this->inventoryProvider
            ->method('getOnHandQuantity')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getSafetyStock')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getScheduledReceipts')
            ->willReturn([]);

        $this->inventoryProvider
            ->method('getLeadTimeDays')
            ->willReturn(7);

        $this->inventoryProvider
            ->method('getReplenishmentType')
            ->willReturn('purchase');

        $this->demandProvider
            ->method('getMasterScheduledProducts')
            ->willReturn(['prod-001']);

        $this->demandProvider
            ->method('getGrossRequirements')
            ->willReturn(['2024-01-15' => 100.0]);

        $this->demandProvider
            ->expects($this->once())
            ->method('deletePlannedOrders')
            ->with('prod-001', $this->horizon);

        $this->demandProvider
            ->expects($this->atLeastOnce())
            ->method('savePlannedOrder');

        $engine = new MrpEngine(
            $this->bomManager,
            $this->inventoryProvider,
            $this->demandProvider,
        );

        $results = $engine->regenerate($this->horizon);

        $this->assertCount(1, $results);
    }

    public function testMrpUsesLotForLotStrategy(): void
    {
        $this->inventoryProvider
            ->method('getOnHandQuantity')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getSafetyStock')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getScheduledReceipts')
            ->willReturn([]);

        $this->inventoryProvider
            ->method('getLeadTimeDays')
            ->willReturn(7);

        $this->inventoryProvider
            ->method('getReplenishmentType')
            ->willReturn('purchase');

        // Exact demand of 100 units
        $this->demandProvider
            ->method('getGrossRequirements')
            ->willReturn(['2024-01-15' => 100.0]);

        $engine = new MrpEngine(
            $this->bomManager,
            $this->inventoryProvider,
            $this->demandProvider,
        );

        $result = $engine->calculate('prod-001', $this->horizon, LotSizingStrategy::LOT_FOR_LOT);

        // With lot-for-lot, the planned order should be exactly what's needed
        $this->assertNotEmpty($result->plannedOrders);
        $this->assertSame(100.0, $result->plannedOrders[0]->quantity);
    }

    public function testMrpCalculatesLeadTimeOffset(): void
    {
        $leadTimeDays = 7;

        $this->inventoryProvider
            ->method('getOnHandQuantity')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getSafetyStock')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getScheduledReceipts')
            ->willReturn([]);

        $this->inventoryProvider
            ->method('getLeadTimeDays')
            ->willReturn($leadTimeDays);

        $this->inventoryProvider
            ->method('getReplenishmentType')
            ->willReturn('purchase');

        // Demand on Jan 15
        $this->demandProvider
            ->method('getGrossRequirements')
            ->willReturn(['2024-01-15' => 100.0]);

        $engine = new MrpEngine(
            $this->bomManager,
            $this->inventoryProvider,
            $this->demandProvider,
        );

        $result = $engine->calculate('prod-001', $this->horizon);

        // Planned order should start 7 days before Jan 15 = Jan 8
        $this->assertNotEmpty($result->plannedOrders);
        $this->assertSame('2024-01-08', $result->plannedOrders[0]->startDate->format('Y-m-d'));
    }

    public function testMrpExplodesMultiLevelBom(): void
    {
        $componentLines = [
            new BomLine(
                productId: 'comp-001',
                quantity: 2.0,
                uomCode: 'EA',
                lineNumber: 10,
            ),
        ];

        $bom = $this->createMock(BomInterface::class);
        $bom->method('getLines')->willReturn($componentLines);

        $this->bomManager
            ->method('getEffective')
            ->willReturnCallback(function ($productId) use ($bom) {
                if ($productId === 'prod-001') {
                    return $bom;
                }
                throw new \Exception('No BOM');
            });

        $this->inventoryProvider
            ->method('getOnHandQuantity')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getSafetyStock')
            ->willReturn(0.0);

        $this->inventoryProvider
            ->method('getScheduledReceipts')
            ->willReturn([]);

        $this->inventoryProvider
            ->method('getLeadTimeDays')
            ->willReturn(7);

        $this->inventoryProvider
            ->method('getReplenishmentType')
            ->willReturn('manufacture');

        $this->demandProvider
            ->method('getGrossRequirements')
            ->willReturn(['2024-01-15' => 100.0]);

        $engine = new MrpEngine(
            $this->bomManager,
            $this->inventoryProvider,
            $this->demandProvider,
        );

        $result = $engine->calculate('prod-001', $this->horizon);

        // Should have requirements for parent and components
        $this->assertNotEmpty($result->materialRequirements);
    }
}
