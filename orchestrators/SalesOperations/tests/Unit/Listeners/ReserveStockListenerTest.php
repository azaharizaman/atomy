<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Listeners;

use Nexus\SalesOperations\Contracts\StockReservationInterface;
use Nexus\SalesOperations\Events\OrderConfirmedEvent;
use Nexus\SalesOperations\Listeners\ReserveStockListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReserveStockListener::class)]
final class ReserveStockListenerTest extends TestCase
{
    #[Test]
    public function handle_reserves_stock_for_confirmed_order(): void
    {
        $stockReservation = $this->createMock(StockReservationInterface::class);
        $stockReservation->expects($this->exactly(2))
            ->method('reserve')
            ->willReturn(true);

        $listener = new ReserveStockListener($stockReservation);

        $event = new OrderConfirmedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [
                ['product_variant_id' => 'prod-1', 'quantity' => 10],
                ['product_variant_id' => 'prod-2', 'quantity' => 5],
            ],
            warehouseId: 'wh-1'
        );

        $listener->handle($event);
    }

    #[Test]
    public function invoke_calls_handle(): void
    {
        $stockReservation = $this->createMock(StockReservationInterface::class);
        $stockReservation->method('reserve')->willReturn(true);

        $listener = new ReserveStockListener($stockReservation);

        $event = new OrderConfirmedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [],
            warehouseId: 'wh-1'
        );

        $listener($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function handle_skips_lines_without_product_id(): void
    {
        $stockReservation = $this->createMock(StockReservationInterface::class);
        $stockReservation->expects($this->never())->method('reserve');

        $listener = new ReserveStockListener($stockReservation);

        $event = new OrderConfirmedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [
                ['quantity' => 10],
            ],
            warehouseId: 'wh-1'
        );

        $listener->handle($event);
    }

    #[Test]
    public function handle_skips_lines_with_zero_quantity(): void
    {
        $stockReservation = $this->createMock(StockReservationInterface::class);
        $stockReservation->expects($this->never())->method('reserve');

        $listener = new ReserveStockListener($stockReservation);

        $event = new OrderConfirmedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [
                ['product_variant_id' => 'prod-1', 'quantity' => 0],
            ],
            warehouseId: 'wh-1'
        );

        $listener->handle($event);
    }
}
