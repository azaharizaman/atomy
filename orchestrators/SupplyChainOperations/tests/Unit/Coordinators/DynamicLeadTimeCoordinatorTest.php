<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\SupplyChainOperations\Coordinators\DynamicLeadTimeCoordinator;
use Nexus\SupplyChainOperations\Contracts\AtpCalculationServiceInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DynamicLeadTimeCoordinator.
 *
 * @covers \Nexus\SupplyChainOperations\Coordinators\DynamicLeadTimeCoordinator
 */
final class DynamicLeadTimeCoordinatorTest extends TestCase
{
    private AtpCalculationServiceInterface $atpCalculationService;
    private StockManagerInterface $stockManager;
    private LoggerInterface $logger;
    private DynamicLeadTimeCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->atpCalculationService = $this->createMock(AtpCalculationServiceInterface::class);
        $this->stockManager = $this->createMock(StockManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new DynamicLeadTimeCoordinator(
            $this->atpCalculationService,
            $this->stockManager,
            $this->logger
        );
    }

    public function test_calculate_atp_returns_available_now_when_stock_sufficient(): void
    {
        $this->stockManager
            ->method('getAvailableStock')
            ->with('product-001', 'warehouse-001')
            ->willReturn(100.0);

        $result = $this->coordinator->calculateAtpDate(
            'product-001',
            50.0,
            'warehouse-001'
        );

        $this->assertTrue($result->availableNow);
        $this->assertFalse($result->requiresProcurement);
    }

    public function test_calculate_atp_with_shortage(): void
    {
        $this->stockManager
            ->method('getAvailableStock')
            ->willReturn(10.0);

        $this->atpCalculationService
            ->method('calculateLeadTimeData')
            ->willReturn([
                'baseDays' => 7.0,
                'variance' => 0.5,
                'varianceBuffer' => 0.825,
                'reliabilityBuffer' => 0.35,
                'seasonalBuffer' => 0,
                'totalDays' => 9,
                'vendorId' => 'vendor-001',
                'vendorAccuracy' => 0.95,
            ]);

        $this->atpCalculationService
            ->method('calculateConfidence')
            ->willReturn(0.85);

        $result = $this->coordinator->calculateAtpDate(
            'product-001',
            50.0,
            'warehouse-001',
            'vendor-001'
        );

        $this->assertFalse($result->availableNow);
        $this->assertTrue($result->requiresProcurement);
        $this->assertSame(40.0, $result->shortageQuantity);
    }

    public function test_calculate_atp_for_multiple_products(): void
    {
        $this->stockManager
            ->method('getAvailableStock')
            ->willReturnCallback(fn($productId) => $productId === 'product-001' ? 100.0 : 0.0);

        $this->atpCalculationService
            ->method('calculateLeadTimeData')
            ->willReturn([
                'baseDays' => 7.0,
                'variance' => 0.0,
                'varianceBuffer' => 0.0,
                'reliabilityBuffer' => 0.0,
                'seasonalBuffer' => 0,
                'totalDays' => 7,
                'vendorId' => 'vendor-001',
                'vendorAccuracy' => 1.0,
            ]);

        $this->atpCalculationService
            ->method('calculateConfidence')
            ->willReturn(1.0);

        $items = [
            ['product_id' => 'product-001', 'quantity' => 50.0, 'warehouse_id' => 'warehouse-001'],
            ['product_id' => 'product-002', 'quantity' => 25.0, 'warehouse_id' => 'warehouse-001'],
        ];

        $results = $this->coordinator->calculateAtpForMultiple($items);

        $this->assertCount(2, $results);
        $this->assertTrue($results['product-001']->availableNow);
        $this->assertFalse($results['product-002']->availableNow);
    }
}
