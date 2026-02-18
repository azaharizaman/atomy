<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\SupplyChainOperations\Coordinators\ReplenishmentCoordinator;
use Nexus\SupplyChainOperations\Services\ReplenishmentForecastService;
use Nexus\AuditLogger\Services\AuditLogManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ReplenishmentCoordinator.
 *
 * @covers \Nexus\SupplyChainOperations\Coordinators\ReplenishmentCoordinator
 */
final class ReplenishmentCoordinatorTest extends TestCase
{
    private StockManagerInterface $stockManager;
    private ProcurementManagerInterface $procurementManager;
    private ReplenishmentForecastService $forecastService;
    private AuditLogManager $auditLogger;
    private LoggerInterface $logger;
    private ReplenishmentCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->stockManager = $this->createMock(StockManagerInterface::class);
        $this->procurementManager = $this->createMock(ProcurementManagerInterface::class);
        $this->forecastService = $this->createMock(ReplenishmentForecastService::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new ReplenishmentCoordinator(
            $this->stockManager,
            $this->procurementManager,
            $this->forecastService,
            $this->auditLogger,
            $this->logger
        );
    }

    public function test_create_auto_requisition_returns_null_for_empty_map(): void
    {
        $result = $this->coordinator->createAutoRequisition(
            'tenant-001',
            'warehouse-001',
            'system',
            []
        );

        $this->assertNull($result);
    }

    public function test_create_auto_requisition_creates_requisition(): void
    {
        $replenishmentMap = ['product-001' => 50.0];

        $requisition = $this->createMock(RequisitionInterface::class);
        $requisition->method('getId')->willReturn('req-001');

        $this->procurementManager
            ->expects($this->once())
            ->method('createRequisition')
            ->willReturn($requisition);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->coordinator->createAutoRequisition(
            'tenant-001',
            'warehouse-001',
            'system',
            $replenishmentMap
        );

        $this->assertSame('req-001', $result);
    }

    public function test_evaluate_product_with_forecast_triggers_reorder(): void
    {
        $this->stockManager
            ->method('getCurrentStock')
            ->with('product-001', 'warehouse-001')
            ->willReturn(10.0);

        $this->forecastService
            ->expects($this->once())
            ->method('evaluateWithForecast')
            ->with('product-001', 'warehouse-001', 10.0)
            ->willReturn([
                'product_id' => 'product-001',
                'warehouse_id' => 'warehouse-001',
                'current_stock' => 10.0,
                'reorder_point' => 15.0,
                'safety_stock' => 20.0,
                'forecast_30d' => 150.0,
                'forecast_7d' => 35.0,
                'suggested_qty' => 160.0,
                'requires_reorder' => true,
                'confidence_factors' => [],
            ]);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->coordinator->evaluateProductWithForecast(
            'tenant-001',
            'product-001',
            'warehouse-001'
        );

        $this->assertNotNull($result);
        $this->assertTrue($result['requires_reorder']);
    }

    public function test_evaluate_product_with_forecast_no_reorder_needed(): void
    {
        $this->stockManager
            ->method('getCurrentStock')
            ->willReturn(1000.0);

        $this->forecastService
            ->expects($this->once())
            ->method('evaluateWithForecast')
            ->willReturn([
                'product_id' => 'product-001',
                'warehouse_id' => 'warehouse-001',
                'current_stock' => 1000.0,
                'reorder_point' => 50.0,
                'safety_stock' => 20.0,
                'forecast_30d' => 30.0,
                'forecast_7d' => 7.0,
                'suggested_qty' => 0.0,
                'requires_reorder' => false,
                'confidence_factors' => [],
            ]);

        $result = $this->coordinator->evaluateProductWithForecast(
            'tenant-001',
            'product-001',
            'warehouse-001'
        );

        $this->assertNotNull($result);
        $this->assertFalse($result['requires_reorder']);
    }

    public function test_create_forecast_based_requisition_creates_when_needed(): void
    {
        $this->stockManager
            ->method('getCurrentStock')
            ->willReturn(5.0);

        $this->forecastService
            ->expects($this->once())
            ->method('evaluateWithForecast')
            ->willReturn([
                'requires_reorder' => true,
                'suggested_qty' => 100.0,
            ]);

        $requisition = $this->createMock(RequisitionInterface::class);
        $requisition->method('getId')->willReturn('req-001');

        $this->procurementManager
            ->expects($this->once())
            ->method('createRequisition')
            ->willReturn($requisition);

        $result = $this->coordinator->createForecastBasedRequisition(
            'tenant-001',
            'warehouse-001',
            'system',
            'product-001'
        );

        $this->assertSame('req-001', $result);
    }

    public function test_create_forecast_based_requisition_returns_null_when_not_needed(): void
    {
        $this->stockManager
            ->method('getCurrentStock')
            ->willReturn(1000.0);

        $this->forecastService
            ->expects($this->once())
            ->method('evaluateWithForecast')
            ->willReturn([
                'requires_reorder' => false,
                'suggested_qty' => 0.0,
            ]);

        $this->procurementManager
            ->expects($this->never())
            ->method('createRequisition');

        $result = $this->coordinator->createForecastBasedRequisition(
            'tenant-001',
            'warehouse-001',
            'system',
            'product-001'
        );

        $this->assertNull($result);
    }
}
