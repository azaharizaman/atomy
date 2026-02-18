<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Services;

use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\StockLevelInterface;
use Nexus\SupplyChainOperations\Services\RegionalOptimizationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class RegionalOptimizationServiceTest extends TestCase
{
    private DistanceCalculatorInterface $distanceCalculator;
    private StockLevelRepositoryInterface $stockLevelRepository;
    private LoggerInterface $logger;
    private RegionalOptimizationService $service;

    protected function setUp(): void
    {
        $this->distanceCalculator = $this->createMock(DistanceCalculatorInterface::class);
        $this->stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new RegionalOptimizationService(
            $this->distanceCalculator,
            $this->stockLevelRepository,
            $this->logger
        );
    }

    public function test_calculate_optimal_distribution_balances_supply(): void
    {
        $tenantId = 'tenant-001';
        $warehouses = [
            ['id' => 'WH-001', 'name' => 'Warehouse 1'],
            ['id' => 'WH-002', 'name' => 'Warehouse 2']
        ];
        $demandForecast = [
            'product-001' => [
                'regional_demand' => 100.0
            ]
        ];

        $stockLevel = $this->createMock(StockLevelInterface::class);
        $stockLevel->method('getQuantity')->willReturn(150.0);

        $this->stockLevelRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturn($stockLevel);

        $result = $this->service->calculateOptimalDistribution(
            $tenantId,
            $warehouses,
            $demandForecast
        );

        $this->assertArrayHasKey('product-001', $result);
        $this->assertSame('balanced', $result['product-001']['status']);
    }

    public function test_calculate_optimal_distribution_handles_shortage(): void
    {
        $tenantId = 'tenant-001';
        $warehouses = [
            ['id' => 'WH-001', 'name' => 'Warehouse 1']
        ];
        $demandForecast = [
            'product-001' => [
                'regional_demand' => 100.0
            ]
        ];

        $stockLevel = $this->createMock(StockLevelInterface::class);
        $stockLevel->method('getQuantity')->willReturn(50.0);

        $this->stockLevelRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($stockLevel);

        $result = $this->service->calculateOptimalDistribution(
            $tenantId,
            $warehouses,
            $demandForecast
        );

        $this->assertSame('shortage', $result['product-001']['status']);
        $this->assertGreaterThan(0, $result['product-001']['shortage_quantity']);
    }

    public function test_calculate_shipping_cost_optimization(): void
    {
        $warehouseLocations = [
            'WH-001' => ['lat' => 40.7128, 'lon' => -74.0060],
            'WH-002' => ['lat' => 34.0522, 'lon' => -118.2437]
        ];

        $this->distanceCalculator
            ->expects($this->once())
            ->method('calculate')
            ->with(40.7128, -74.0060, 34.0522, -118.2437)
            ->willReturn(3944.0);

        $cost = $this->service->calculateShippingCostOptimization(
            'WH-001',
            'WH-002',
            100.0,
            $warehouseLocations
        );

        $this->assertGreaterThan(0, $cost);
    }

    public function test_find_nearest_warehouse_with_stock_returns_best_option(): void
    {
        $tenantId = 'tenant-001';
        $productId = 'product-001';
        $requiredQuantity = 10.0;

        $candidateWarehouses = [
            ['id' => 'WH-001', 'lat' => 40.7128, 'lon' => -74.0060],
            ['id' => 'WH-002', 'lat' => 34.0522, 'lon' => -118.2437]
        ];

        $warehouseLocations = [
            'WH-001' => ['lat' => 40.7128, 'lon' => -74.0060],
            'WH-002' => ['lat' => 34.0522, 'lon' => -118.2437]
        ];

        $stockLevel = $this->createMock(StockLevelInterface::class);
        $stockLevel->method('getQuantity')->willReturn(50.0);

        $this->stockLevelRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($stockLevel);

        $this->distanceCalculator
            ->expects($this->once())
            ->method('calculate')
            ->willReturn(100.0);

        $result = $this->service->findNearestWarehouseWithStock(
            $tenantId,
            $productId,
            $candidateWarehouses,
            $requiredQuantity,
            $warehouseLocations
        );

        $this->assertNotNull($result);
        $this->assertArrayHasKey('warehouse_id', $result);
    }
}
