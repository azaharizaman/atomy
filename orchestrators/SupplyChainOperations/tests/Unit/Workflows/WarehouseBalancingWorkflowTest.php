<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Workflows;

use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\TransferManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Nexus\SupplyChainOperations\Workflows\WarehouseBalancing\WarehouseBalancingWorkflow;
use Nexus\AuditLogger\Services\AuditLogManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class WarehouseBalancingWorkflowTest extends TestCase
{
    private GeoRepositoryInterface $geoRepository;
    private StockLevelRepositoryInterface $stockLevelRepository;
    private WarehouseRepositoryInterface $warehouseRepository;
    private TransferManagerInterface $transferManager;
    private AuditLogManager $auditLogger;
    private LoggerInterface $logger;
    private WarehouseBalancingWorkflow $workflow;

    protected function setUp(): void
    {
        $this->geoRepository = $this->createMock(GeoRepositoryInterface::class);
        $this->stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $this->warehouseRepository = $this->createMock(WarehouseRepositoryInterface::class);
        $this->transferManager = $this->createMock(TransferManagerInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->workflow = new WarehouseBalancingWorkflow(
            $this->geoRepository,
            $this->stockLevelRepository,
            $this->warehouseRepository,
            $this->transferManager,
            $this->auditLogger,
            $this->logger
        );
    }

    public function test_analyze_and_balance_creates_transfer_for_deficit(): void
    {
        $tenantId = 'tenant-001';

        $warehouse1 = $this->createMock(WarehouseInterface::class);
        $warehouse1->method('getId')->willReturn('WH-001');
        $warehouse1->method('getRegionId')->willReturn('region-001');
        $warehouse1->method('getLocationId')->willReturn('loc-001');

        $warehouse2 = $this->createMock(WarehouseInterface::class);
        $warehouse2->method('getId')->willReturn('WH-002');
        $warehouse2->method('getRegionId')->willReturn('region-001');
        $warehouse2->method('getLocationId')->willReturn('loc-002');

        $this->warehouseRepository
            ->expects($this->once())
            ->method('findByTenant')
            ->with($tenantId)
            ->willReturn([$warehouse1, $warehouse2]);

        $stockLevel1 = $this->createMock(\Nexus\Inventory\Contracts\StockLevelInterface::class);
        $stockLevel1->method('getProductId')->willReturn('product-001');
        $stockLevel1->method('getQuantity')->willReturn(150.0);
        $stockLevel1->method('getReorderPoint')->willReturn(50.0);

        $stockLevel2 = $this->createMock(\Nexus\Inventory\Contracts\StockLevelInterface::class);
        $stockLevel2->method('getProductId')->willReturn('product-001');
        $stockLevel2->method('getQuantity')->willReturn(20.0);
        $stockLevel2->method('getReorderPoint')->willReturn(50.0);

        $this->stockLevelRepository
            ->expects($this->exactly(2))
            ->method('findByWarehouse')
            ->willReturnOnConsecutiveCalls([$stockLevel1], [$stockLevel2]);

        $this->geoRepository
            ->expects($this->exactly(2))
            ->method('getLocation')
            ->willReturn(['lat' => 40.7128, 'lon' => -74.0060]);

        $this->transferManager
            ->expects($this->once())
            ->method('createTransfer')
            ->willReturn('TO-001');

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->workflow->analyzeAndBalance($tenantId);

        $this->assertSame(2, $result->warehousesAnalyzed);
        $this->assertNotEmpty($result->transfersCreated);
    }

    public function test_analyze_and_balance_with_region_filter(): void
    {
        $tenantId = 'tenant-001';
        $regionId = 'region-001';

        $warehouse = $this->createMock(WarehouseInterface::class);
        $warehouse->method('getId')->willReturn('WH-001');
        $warehouse->method('getRegionId')->willReturn($regionId);
        $warehouse->method('getLocationId')->willReturn('loc-001');

        $this->warehouseRepository
            ->expects($this->once())
            ->method('findByTenant')
            ->with($tenantId)
            ->willReturn([$warehouse]);

        $stockLevel = $this->createMock(\Nexus\Inventory\Contracts\StockLevelInterface::class);
        $stockLevel->method('getProductId')->willReturn('product-001');
        $stockLevel->method('getQuantity')->willReturn(100.0);
        $stockLevel->method('getReorderPoint')->willReturn(50.0);

        $this->stockLevelRepository
            ->expects($this->once())
            ->method('findByWarehouse')
            ->willReturn([$stockLevel]);

        $this->geoRepository
            ->expects($this->once())
            ->method('getLocation')
            ->willReturn(['lat' => 40.7128, 'lon' => -74.0060]);

        $this->transferManager
            ->expects($this->never())
            ->method('createTransfer');

        $result = $this->workflow->analyzeAndBalance($tenantId, $regionId);

        $this->assertSame($regionId, $result->regionId);
    }
}
