<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Listeners;

use Nexus\Sales\Events\SalesOrderConfirmedEvent;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderLineInterface;
use Nexus\SupplyChainOperations\Contracts\DropshipCoordinatorInterface;
use Nexus\SupplyChainOperations\DataProviders\DropshipDataProvider;
use Nexus\SupplyChainOperations\Listeners\DropshipListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DropshipListenerTest extends TestCase
{
    private DropshipCoordinatorInterface $dropshipCoordinator;
    private DropshipDataProvider $dataProvider;
    private LoggerInterface $logger;
    private DropshipListener $listener;

    protected function setUp(): void
    {
        $this->dropshipCoordinator = $this->createMock(DropshipCoordinatorInterface::class);
        $this->dataProvider = $this->createMock(DropshipDataProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new DropshipListener(
            $this->dropshipCoordinator,
            $this->dataProvider,
            $this->logger
        );
    }

    public function test_on_sales_order_confirmed_skips_non_dropship_orders(): void
    {
        $salesOrder = $this->createMock(SalesOrderInterface::class);
        $salesOrder->method('getPreferredWarehouseId')->willReturn('WH-STANDARD');
        $salesOrder->method('getMetadata')->willReturn([]);

        $event = new SalesOrderConfirmedEvent($salesOrder);

        $this->dropshipCoordinator
            ->expects($this->never())
            ->method('createDropshipPo');

        $this->listener->onSalesOrderConfirmed($event);
    }

    public function test_on_sales_order_confirmed_processes_dropship_orders(): void
    {
        $line = $this->createMock(SalesOrderLineInterface::class);
        $line->method('getProductVariantId')->willReturn('product-001');
        $line->method('getQuantity')->willReturn(5.0);

        $salesOrder = $this->createMock(SalesOrderInterface::class);
        $salesOrder->method('getPreferredWarehouseId')->willReturn('WH-DS');
        $salesOrder->method('getMetadata')->willReturn([]);
        $salesOrder->method('getTenantId')->willReturn('tenant-001');
        $salesOrder->method('getOrderNumber')->willReturn('SO-001');
        $salesOrder->method('getLines')->willReturn([$line]);

        $event = new SalesOrderConfirmedEvent($salesOrder);

        $this->dataProvider
            ->expects($this->once())
            ->method('groupLinesByVendor')
            ->willReturn(['vendor-001' => [$line]]);

        $this->dropshipCoordinator
            ->expects($this->once())
            ->method('createDropshipPo')
            ->willReturn('PO-001');

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->listener->onSalesOrderConfirmed($event);
    }

    public function test_on_sales_order_confirmed_handles_dropship_metadata(): void
    {
        $salesOrder = $this->createMock(SalesOrderInterface::class);
        $salesOrder->method('getPreferredWarehouseId')->willReturn('WH-STANDARD');
        $salesOrder->method('getMetadata')->willReturn(['is_dropship' => true]);
        $salesOrder->method('getTenantId')->willReturn('tenant-001');
        $salesOrder->method('getOrderNumber')->willReturn('SO-002');
        $salesOrder->method('getLines')->willReturn([]);

        $event = new SalesOrderConfirmedEvent($salesOrder);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('has no lines'));

        $this->listener->onSalesOrderConfirmed($event);
    }
}
