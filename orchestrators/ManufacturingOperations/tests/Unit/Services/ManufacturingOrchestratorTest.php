<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Tests\Unit\Services;

use Nexus\Identity\Contracts\AuthContextInterface;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\BomProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\CostingProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\CurrencyProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\InventoryProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\ManufacturingProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\QualityProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\UomProviderInterface;
use Nexus\ManufacturingOperations\Contracts\Providers\WarehouseProviderInterface;
use Nexus\ManufacturingOperations\DTOs\BomExplosionResult;
use Nexus\ManufacturingOperations\DTOs\CostCalculationResult;
use Nexus\ManufacturingOperations\DTOs\CurrencyCode;
use Nexus\ManufacturingOperations\DTOs\ProductionOrder;
use Nexus\ManufacturingOperations\DTOs\ProductionOrderRequest;
use Nexus\ManufacturingOperations\DTOs\ProductionOrderStatus;
use Nexus\ManufacturingOperations\DTOs\StockReservationResult;
use Nexus\ManufacturingOperations\Exceptions\ManufacturingOperationsException;
use Nexus\ManufacturingOperations\Exceptions\StockShortageException;
use Nexus\ManufacturingOperations\Services\ManufacturingOrchestrator;
use Nexus\ManufacturingOperations\Events\OrderCompleted;
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
    private MockObject&UomProviderInterface $uomProvider;
    private MockObject&WarehouseProviderInterface $warehouseProvider;
    private MockObject&CurrencyProviderInterface $currencyProvider;
    private MockObject&AuthContextInterface $authContext;
    private MockObject&PolicyEvaluatorInterface $policyEvaluator;
    private MockObject&LoggerInterface $logger;
    private MockObject&EventDispatcherInterface $dispatcher;
    private MockObject&UserInterface $user;

    protected function setUp(): void
    {
        $this->manufacturingProvider = $this->createMock(ManufacturingProviderInterface::class);
        $this->bomProvider = $this->createMock(BomProviderInterface::class);
        $this->inventoryProvider = $this->createMock(InventoryProviderInterface::class);
        $this->qualityProvider = $this->createMock(QualityProviderInterface::class);
        $this->costingProvider = $this->createMock(CostingProviderInterface::class);
        $this->uomProvider = $this->createMock(UomProviderInterface::class);
        $this->warehouseProvider = $this->createMock(WarehouseProviderInterface::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->authContext = $this->createMock(AuthContextInterface::class);
        $this->policyEvaluator = $this->createMock(PolicyEvaluatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->user = $this->createMock(UserInterface::class);

        $this->orchestrator = new ManufacturingOrchestrator(
            $this->manufacturingProvider,
            $this->bomProvider,
            $this->inventoryProvider,
            $this->qualityProvider,
            $this->costingProvider,
            $this->uomProvider,
            $this->warehouseProvider,
            $this->currencyProvider,
            $this->authContext,
            $this->policyEvaluator,
            $this->logger,
            $this->dispatcher
        );

        $this->authContext->method('getCurrentUser')->willReturn($this->user);
        $this->policyEvaluator->method('evaluate')->willReturn(true);
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

        $costResult = new CostCalculationResult('100.00', '50.00', '10.00', CurrencyCode::USD);
        $this->costingProvider->expects($this->once())
            ->method('calculateEstimatedCost')
            ->willReturn($costResult);

        $expectedOrder = new ProductionOrder(
            id: 'ORD-1',
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: ProductionOrderStatus::Planned,
            dueDate: $request->dueDate
        );
        
        $this->manufacturingProvider->expects($this->once())
            ->method('createOrder')
            ->willReturn($expectedOrder);

        $this->dispatcher->expects($this->once())->method('dispatch');

        $result = $this->orchestrator->planProduction('tenant-1', $request);

        $this->assertSame($expectedOrder, $result);
    }

    #[Test]
    public function releaseOrder_success(): void
    {
        $orderId = 'ORD-1';
        $orderPlanned = new ProductionOrder(
            id: $orderId,
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: ProductionOrderStatus::Planned,
            dueDate: new \DateTimeImmutable('+1 week')
        );
        
        $orderReleased = new ProductionOrder(
            id: $orderId,
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: ProductionOrderStatus::Released,
            dueDate: new \DateTimeImmutable('+1 week')
        );

        $this->manufacturingProvider->expects($this->exactly(2))
            ->method('getOrder')
            ->willReturnOnConsecutiveCalls($orderPlanned, $orderReleased);

        $bomResult = new BomExplosionResult('BOM-1', 'P-100', ['C-1' => 2.0], '1.0');
        $this->bomProvider->expects($this->once())
            ->method('getBom')
            ->willReturn($bomResult);

        $reservationResult = new StockReservationResult('RES-1', true);
        $this->inventoryProvider->expects($this->once())
            ->method('reserveStock')
            ->willReturn($reservationResult);

        $this->qualityProvider->expects($this->once())
            ->method('createInspection')
            ->willReturn('INSP-1');

        $this->manufacturingProvider->expects($this->once())
            ->method('updateOrderStatus')
            ->with('tenant-1', $orderId, ProductionOrderStatus::Released);

        $this->dispatcher->expects($this->once())->method('dispatch');

        $result = $this->orchestrator->releaseOrder('tenant-1', $orderId);
        
        $this->assertEquals(ProductionOrderStatus::Released, $result->status);
    }

    #[Test]
    public function completeOrder_success(): void
    {
        $orderId = 'ORD-1';
        $order = new ProductionOrder(
            id: $orderId,
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: ProductionOrderStatus::Released,
            dueDate: new \DateTimeImmutable('+1 week'),
            reservationId: 'RES-1'
        );
        
        $orderCompleted = new ProductionOrder(
            id: $orderId,
            orderNumber: 'PO-001',
            productId: 'P-100',
            quantity: 10.0,
            status: ProductionOrderStatus::Completed,
            dueDate: new \DateTimeImmutable('+1 week'),
            reservationId: 'RES-1'
        );

        $this->manufacturingProvider->expects($this->exactly(2))
            ->method('getOrder')
            ->willReturnOnConsecutiveCalls($order, $orderCompleted);
            
        $this->qualityProvider->method('checkCompliance')->willReturn(true);
        
        $this->costingProvider->method('getMaterialCosts')->willReturn([]);
        $this->costingProvider->method('getLaborCosts')->willReturn([]);
        $this->costingProvider->method('getOverheadCosts')->willReturn([]);
        $this->costingProvider->method('getCurrencyForOrder')->willReturn(CurrencyCode::USD);
        $this->warehouseProvider->method('resolveWarehouse')->willReturn('WH-1');

        $this->inventoryProvider->expects($this->once())->method('issueStock');
        $this->inventoryProvider->expects($this->once())->method('receiveStock');
        $this->costingProvider->expects($this->once())->method('recordActualCost');
        
        $this->manufacturingProvider->expects($this->once())
            ->method('updateOrderStatus')
            ->with('tenant-1', $orderId, ProductionOrderStatus::Completed);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderCompleted::class));

        $result = $this->orchestrator->completeOrder('tenant-1', $orderId);
        
        $this->assertSame($orderCompleted, $result);
    }
}
