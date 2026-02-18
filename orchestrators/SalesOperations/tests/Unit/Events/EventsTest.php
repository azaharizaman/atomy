<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Events;

use Nexus\SalesOperations\Events\OrderCreatedEvent;
use Nexus\SalesOperations\Events\OrderConfirmedEvent;
use Nexus\SalesOperations\Events\OrderCancelledEvent;
use Nexus\SalesOperations\Events\ShipmentCreatedEvent;
use Nexus\SalesOperations\Events\InvoiceGeneratedEvent;
use Nexus\SalesOperations\Events\PaymentReceivedEvent;
use Nexus\SalesOperations\Events\QuotationCreatedEvent;
use Nexus\SalesOperations\Events\QuotationAcceptedEvent;
use Nexus\SalesOperations\Events\CommissionCalculatedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderCreatedEvent::class)]
#[CoversClass(OrderConfirmedEvent::class)]
#[CoversClass(OrderCancelledEvent::class)]
#[CoversClass(ShipmentCreatedEvent::class)]
#[CoversClass(InvoiceGeneratedEvent::class)]
#[CoversClass(PaymentReceivedEvent::class)]
#[CoversClass(QuotationCreatedEvent::class)]
#[CoversClass(QuotationAcceptedEvent::class)]
#[CoversClass(CommissionCalculatedEvent::class)]
final class EventsTest extends TestCase
{
    #[Test]
    public function order_created_event_holds_data(): void
    {
        $event = new OrderCreatedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [['product_id' => 'prod-1', 'quantity' => 10]],
            salespersonId: 'sp-1'
        );

        $this->assertSame('tenant-1', $event->tenantId);
        $this->assertSame('order-1', $event->orderId);
        $this->assertSame('SO-001', $event->orderNumber);
        $this->assertSame('cust-1', $event->customerId);
        $this->assertSame(1000.0, $event->totalAmount);
        $this->assertSame('MYR', $event->currencyCode);
        $this->assertCount(1, $event->lines);
        $this->assertSame('sp-1', $event->salespersonId);
    }

    #[Test]
    public function order_confirmed_event_holds_data(): void
    {
        $event = new OrderConfirmedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [],
            warehouseId: 'wh-1',
            confirmedBy: 'user-1'
        );

        $this->assertSame('order-1', $event->orderId);
        $this->assertSame('wh-1', $event->warehouseId);
        $this->assertSame('user-1', $event->confirmedBy);
    }

    #[Test]
    public function order_cancelled_event_holds_data(): void
    {
        $event = new OrderCancelledEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            reason: 'Customer request',
            cancelledBy: 'user-1'
        );

        $this->assertSame('order-1', $event->orderId);
        $this->assertSame('Customer request', $event->reason);
        $this->assertSame('user-1', $event->cancelledBy);
    }

    #[Test]
    public function shipment_created_event_holds_data(): void
    {
        $event = new ShipmentCreatedEvent(
            tenantId: 'tenant-1',
            shipmentId: 'ship-1',
            shipmentNumber: 'SH-001',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: [],
            trackingNumber: 'TRACK-123',
            shippedBy: 'user-1'
        );

        $this->assertSame('ship-1', $event->shipmentId);
        $this->assertSame('SH-001', $event->shipmentNumber);
        $this->assertSame('TRACK-123', $event->trackingNumber);
    }

    #[Test]
    public function invoice_generated_event_holds_data(): void
    {
        $dueDate = new \DateTimeImmutable('2024-02-15');

        $event = new InvoiceGeneratedEvent(
            tenantId: 'tenant-1',
            invoiceId: 'inv-1',
            invoiceNumber: 'INV-001',
            orderId: 'order-1',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            balanceDue: 1000.0,
            currencyCode: 'MYR',
            dueDate: $dueDate
        );

        $this->assertSame('inv-1', $event->invoiceId);
        $this->assertSame('INV-001', $event->invoiceNumber);
        $this->assertSame(1000.0, $event->balanceDue);
        $this->assertSame($dueDate, $event->dueDate);
    }

    #[Test]
    public function payment_received_event_holds_data(): void
    {
        $event = new PaymentReceivedEvent(
            tenantId: 'tenant-1',
            paymentId: 'pay-1',
            invoiceId: 'inv-1',
            orderId: 'order-1',
            customerId: 'cust-1',
            amount: 500.0,
            currencyCode: 'MYR',
            paymentMethod: 'bank_transfer',
            salespersonId: 'sp-1'
        );

        $this->assertSame('pay-1', $event->paymentId);
        $this->assertSame(500.0, $event->amount);
        $this->assertSame('bank_transfer', $event->paymentMethod);
        $this->assertSame('sp-1', $event->salespersonId);
    }

    #[Test]
    public function quotation_created_event_holds_data(): void
    {
        $validUntil = new \DateTimeImmutable('2024-03-01');

        $event = new QuotationCreatedEvent(
            tenantId: 'tenant-1',
            quotationId: 'quote-1',
            quotationNumber: 'QT-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            validUntil: $validUntil
        );

        $this->assertSame('quote-1', $event->quotationId);
        $this->assertSame('QT-001', $event->quotationNumber);
        $this->assertSame($validUntil, $event->validUntil);
    }

    #[Test]
    public function quotation_accepted_event_holds_data(): void
    {
        $event = new QuotationAcceptedEvent(
            tenantId: 'tenant-1',
            quotationId: 'quote-1',
            quotationNumber: 'QT-001',
            customerId: 'cust-1',
            orderId: 'order-1'
        );

        $this->assertSame('quote-1', $event->quotationId);
        $this->assertSame('order-1', $event->orderId);
    }

    #[Test]
    public function commission_calculated_event_holds_data(): void
    {
        $event = new CommissionCalculatedEvent(
            tenantId: 'tenant-1',
            commissionId: 'comm-1',
            salespersonId: 'sp-1',
            orderId: 'order-1',
            paymentId: 'pay-1',
            commissionAmount: 100.0,
            currencyCode: 'MYR',
            rate: 10.0,
            basis: 'gross_profit'
        );

        $this->assertSame('comm-1', $event->commissionId);
        $this->assertSame('sp-1', $event->salespersonId);
        $this->assertSame(100.0, $event->commissionAmount);
        $this->assertSame(10.0, $event->rate);
        $this->assertSame('gross_profit', $event->basis);
    }
}
