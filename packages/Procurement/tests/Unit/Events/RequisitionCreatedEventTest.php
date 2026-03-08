<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Events;

use Nexus\Procurement\Events\RequisitionCreatedEvent;
use PHPUnit\Framework\TestCase;

final class RequisitionCreatedEventTest extends TestCase
{
    public function test_event_holds_all_properties(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-15 10:00:00');
        $event = new RequisitionCreatedEvent(
            requisitionId: 'req-1',
            tenantId: 'tenant-1',
            requisitionNumber: 'REQ-001',
            requestedBy: 'user-1',
            departmentId: 'dept-1',
            lineItems: [['lineId' => 'L1', 'productId' => 'P1', 'description' => 'Item', 'quantity' => 1.0, 'unitOfMeasure' => 'EA', 'estimatedUnitPrice' => 1000, 'currency' => 'USD', 'requestedDeliveryDate' => null]],
            totalEstimatedAmountCents: 100000,
            currency: 'USD',
            costCenterId: null,
            projectId: null,
            createdAt: $createdAt
        );

        self::assertSame('req-1', $event->requisitionId);
        self::assertSame('tenant-1', $event->tenantId);
        self::assertSame('REQ-001', $event->requisitionNumber);
        self::assertSame('user-1', $event->requestedBy);
        self::assertSame('dept-1', $event->departmentId);
        self::assertCount(1, $event->lineItems);
        self::assertSame(100000, $event->totalEstimatedAmountCents);
        self::assertSame('USD', $event->currency);
        self::assertNull($event->costCenterId);
        self::assertNull($event->projectId);
        self::assertSame($createdAt, $event->createdAt);
    }
}
