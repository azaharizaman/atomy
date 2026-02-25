<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Tests\Unit\Services;

use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\BomProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\CostingProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\InventoryProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\ManufacturingProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers\QualityProviderInterface;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\BomExplosionResult;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\BomLookupRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\CostCalculationResult;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrder;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrderRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\StockReservationResult;
use Nexus\Orchestrators\ManufacturingOperations\Exceptions\ManufacturingOperationsException;
use Nexus\Orchestrators\ManufacturingOperations\Exceptions\StockShortageException;
use Nexus\Orchestrators\ManufacturingOperations\Services\ManufacturingOrchestrator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(ManufacturingOrchestrator::class)]
final class ManufacturingOrchestratorTest extends TestCase
{
    private ManufacturingOrchestrator $orchestrator;
    private MockObject&ManufacturingProviderInterface $manufacturingProvider;
    private MockObject&BomProviderInterface $bomProvider;
    private MockObject&InventoryProviderInterface $inventoryProvider;
    private MockObject&QualityProviderInterface $qualityProvider;
    private MockObject&CostingProviderInterface $costingProvider;
    private MockObject&LoggerInterface $logger;
    private MockObject&EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->manufacturingProvider = $this->createMock(ManufacturingProviderInterface::class);
        $this->bomProvider = $this->createMock(BomProviderInterface::class);
        $this->inventoryProvider = $this->createMock(InventoryProviderInterface::class);
        $this->qualityProvider = $this->createMock(QualityProviderInterface::class);
        $this->costingProvider = $this->createMock(CostingProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->orchestrator = new ManufacturingOrchestrator(
            $this->manufacturingProvider,
            $this->bomProvider,
            $this->inventoryProvider,
            $this->qualityProvider,
            $this->costingProvider,
            $this->logger,
            $this->dispatcher
        );
    }

    #[Test]
    public function planProduction_success(): void
    {
        $request = new ProductionOrderRequest(
            productId: 'P-100',
            quantity: 10.0,
            dueDate: new \DateTimeImmutable('+1 week')
        );

        $bomResult = new BomExplosionResult('BOM-1', 'P-100', ['C-1' => 2.0], '1.0');
        $this->bomProvider->expects($this->once())
            ->method('getBom')
            ->willReturn($bomResult);

        $costResult = new CostCalculationResult(100.0, 50.0, 10.0, 'USD');
        $this->costingProvider->expects($this->once())
            ->method('calculateEstimatedCost')
            ->willReturn($costResult);

        $expectedOrder = new ProductionOrder(
            id: 'ORD-1',
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: 'Planned',
            dueDate: $request->dueDate
        );
        $this->manufacturingProvider->expects($this->once())
            ->method('createOrder')
            ->willReturn($expectedOrder);

        $result = $this->orchestrator->planProduction('tenant-1', $request);

        $this->assertSame($expectedOrder, $result);
    }

    #[Test]
    public function planProduction_fails_with_invalid_bom(): void
    {
        $request = new ProductionOrderRequest(
            productId: 'P-INVALID',
            quantity: 10.0,
            dueDate: new \DateTimeImmutable('+1 week')
        );

        $this->bomProvider->expects($this->once())
            ->method('getBom')
            ->willThrowException(new \Exception('BOM not found'));

        $this->expectException(ManufacturingOperationsException::class);
        $this->expectExceptionMessage("Valid BOM not found for product P-INVALID");

        $this->orchestrator->planProduction('tenant-1', $request);
    }

    #[Test]
    public function releaseOrder_success(): void
    {
        $orderId = 'ORD-1';
        $order = new ProductionOrder(
            id: $orderId,
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: 'Planned',
            dueDate: new \DateTimeImmutable('+1 week')
        );

        $this->manufacturingProvider->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $bomResult = new BomExplosionResult('BOM-1', 'P-100', ['C-1' => 2.0], '1.0');
        $this->bomProvider->expects($this->once())
            ->method('getBom')
            ->willReturn($bomResult);

        $this->inventoryProvider->expects($this->once())
            ->method('checkStockAvailability')
            ->willReturn(true);

        $reservationResult = new StockReservationResult('RES-1', true);
        $this->inventoryProvider->expects($this->once())
            ->method('reserveStock')
            ->willReturn($reservationResult);

        $this->qualityProvider->expects($this->once())
            ->method('createInspection');

        $this->manufacturingProvider->expects($this->once())
            ->method('updateOrderStatus')
            ->with('tenant-1', $orderId, 'Released');

        $this->orchestrator->releaseOrder('tenant-1', $orderId);
    }

    #[Test]
    public function releaseOrder_fails_with_stock_shortage(): void
    {
        $orderId = 'ORD-1';
        $order = new ProductionOrder(
            id: $orderId,
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: 'Planned',
            dueDate: new \DateTimeImmutable('+1 week')
        );

        $this->manufacturingProvider->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $bomResult = new BomExplosionResult('BOM-1', 'P-100', ['C-1' => 2.0], '1.0');
        $this->bomProvider->expects($this->once())
            ->method('getBom')
            ->willReturn($bomResult);

        $this->inventoryProvider->expects($this->once())
            ->method('checkStockAvailability')
            ->willReturn(false);

        $this->expectException(StockShortageException::class);
        $this->orchestrator->releaseOrder('tenant-1', $orderId);
    }
}
