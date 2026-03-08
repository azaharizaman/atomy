<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Events;

use Nexus\Procurement\Events\PurchaseOrderCreatedEvent;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderCreatedEventTest extends TestCase
{
    public function test_event_holds_all_properties(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-15 10:00:00');
        $event = new PurchaseOrderCreatedEvent(
            purchaseOrderId: 'po-1',
            tenantId: 'tenant-1',
            purchaseOrderNumber: 'PO-001',
            vendorId: 'vendor-1',
            requisitionId: 'req-1',
            lineItems: [['lineId' => 'L1', 'productId' => 'P1', 'description' => 'Item', 'quantity' => 1.0, 'unitOfMeasure' => 'EA', 'unitPriceCents' => 1000, 'currency' => 'USD', 'taxCode' => null, 'expectedDeliveryDate' => null]],
            totalAmountCents: 100000,
            currency: 'USD',
            paymentTerms: 'Net 30',
            contractId: null,
            createdAt: $createdAt
        );

        self::assertSame('po-1', $event->purchaseOrderId);
        self::assertSame('tenant-1', $event->tenantId);
        self::assertSame('PO-001', $event->purchaseOrderNumber);
        self::assertSame('vendor-1', $event->vendorId);
        self::assertSame('req-1', $event->requisitionId);
        self::assertCount(1, $event->lineItems);
        self::assertSame(100000, $event->totalAmountCents);
        self::assertSame('USD', $event->currency);
        self::assertSame('Net 30', $event->paymentTerms);
        self::assertNull($event->contractId);
        self::assertSame($createdAt, $event->createdAt);
    }
}
