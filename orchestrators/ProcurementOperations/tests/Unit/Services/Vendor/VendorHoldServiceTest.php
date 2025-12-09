<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Vendor;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Party\Contracts\VendorInterface;
use Nexus\Party\Contracts\VendorPersistInterface;
use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\ProcurementOperations\DTOs\VendorHoldRequest;
use Nexus\ProcurementOperations\Enums\VendorHoldReason;
use Nexus\ProcurementOperations\Events\VendorBlockedEvent;
use Nexus\ProcurementOperations\Events\VendorUnblockedEvent;
use Nexus\ProcurementOperations\Services\Vendor\VendorHoldService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

#[CoversClass(VendorHoldService::class)]
final class VendorHoldServiceTest extends TestCase
{
    private VendorQueryInterface&MockObject $vendorQuery;
    private VendorPersistInterface&MockObject $vendorPersist;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private AuditLogManagerInterface&MockObject $auditLogger;
    private VendorHoldService $service;

    protected function setUp(): void
    {
        $this->vendorQuery = $this->createMock(VendorQueryInterface::class);
        $this->vendorPersist = $this->createMock(VendorPersistInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManagerInterface::class);

        $this->service = new VendorHoldService(
            vendorQuery: $this->vendorQuery,
            vendorPersist: $this->vendorPersist,
            eventDispatcher: $this->eventDispatcher,
            auditLogger: $this->auditLogger
        );
    }

    private function createVendorMock(array $activeHolds = []): VendorInterface&MockObject
    {
        $vendor = $this->createMock(VendorInterface::class);
        $vendor->method('getId')->willReturn('vendor-1');
        $vendor->method('getName')->willReturn('Acme Corp');
        $vendor->method('getActiveHolds')->willReturn($activeHolds);
        return $vendor;
    }

    #[Test]
    public function applyHold_applies_hold_to_vendor(): void
    {
        $vendor = $this->createVendorMock();

        $this->vendorQuery->method('findById')
            ->with('vendor-1')
            ->willReturn($vendor);

        $vendor->expects($this->once())
            ->method('addHold')
            ->with(
                reason: 'fraud_suspected',
                appliedBy: 'user-1',
                notes: 'Suspicious activity detected',
                effectiveUntil: null
            );

        $this->vendorPersist->expects($this->once())
            ->method('update')
            ->with($vendor);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn ($event) =>
                $event instanceof VendorBlockedEvent &&
                $event->vendorId === 'vendor-1' &&
                $event->reason === VendorHoldReason::FRAUD_SUSPECTED
            ));

        $this->auditLogger->expects($this->once())
            ->method('log');

        $request = new VendorHoldRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            reason: VendorHoldReason::FRAUD_SUSPECTED,
            appliedBy: 'user-1',
            notes: 'Suspicious activity detected'
        );

        $this->service->applyHold($request);
    }

    #[Test]
    public function applyHold_throws_when_vendor_not_found(): void
    {
        $this->vendorQuery->method('findById')
            ->willReturn(null);

        $request = new VendorHoldRequest(
            tenantId: 'tenant-1',
            vendorId: 'invalid-vendor',
            reason: VendorHoldReason::COMPLIANCE_PENDING,
            appliedBy: 'user-1'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Vendor invalid-vendor not found');

        $this->service->applyHold($request);
    }

    #[Test]
    public function removeHold_removes_hold_from_vendor(): void
    {
        $vendor = $this->createVendorMock([
            ['reason' => 'compliance_pending']
        ]);

        $this->vendorQuery->method('findById')
            ->with('vendor-1')
            ->willReturn($vendor);

        $vendor->expects($this->once())
            ->method('removeHold')
            ->with('compliance_pending', 'user-1', 'Documents verified');

        $this->vendorPersist->expects($this->once())
            ->method('update')
            ->with($vendor);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn ($event) =>
                $event instanceof VendorUnblockedEvent &&
                $event->vendorId === 'vendor-1' &&
                $event->reason === VendorHoldReason::COMPLIANCE_PENDING
            ));

        $this->service->removeHold(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            reason: VendorHoldReason::COMPLIANCE_PENDING,
            removedBy: 'user-1',
            notes: 'Documents verified'
        );
    }

    #[Test]
    public function hasHold_returns_true_when_hold_exists(): void
    {
        $vendor = $this->createVendorMock([
            ['reason' => 'compliance_pending']
        ]);

        $this->vendorQuery->method('findById')
            ->willReturn($vendor);

        $result = $this->service->hasHold('vendor-1', VendorHoldReason::COMPLIANCE_PENDING);

        $this->assertTrue($result);
    }

    #[Test]
    public function hasHold_returns_false_when_hold_not_exists(): void
    {
        $vendor = $this->createVendorMock([
            ['reason' => 'compliance_pending']
        ]);

        $this->vendorQuery->method('findById')
            ->willReturn($vendor);

        $result = $this->service->hasHold('vendor-1', VendorHoldReason::FRAUD_SUSPECTED);

        $this->assertFalse($result);
    }

    #[Test]
    public function getActiveHolds_returns_hold_reasons(): void
    {
        $vendor = $this->createVendorMock([
            ['reason' => 'compliance_pending'],
            ['reason' => 'certificate_expired'],
        ]);

        $this->vendorQuery->method('findById')
            ->willReturn($vendor);

        $holds = $this->service->getActiveHolds('vendor-1');

        $this->assertCount(2, $holds);
        $this->assertContains(VendorHoldReason::COMPLIANCE_PENDING, $holds);
        $this->assertContains(VendorHoldReason::CERTIFICATE_EXPIRED, $holds);
    }

    #[Test]
    public function clearAllHolds_removes_all_holds(): void
    {
        $vendor = $this->createVendorMock([
            ['reason' => 'compliance_pending'],
            ['reason' => 'certificate_expired'],
        ]);

        $this->vendorQuery->method('findById')
            ->willReturn($vendor);

        // Expect removeHold to be called twice
        $this->vendorPersist->expects($this->exactly(2))
            ->method('update');

        $clearedCount = $this->service->clearAllHolds(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            clearedBy: 'user-1',
            notes: 'Cleared by admin'
        );

        $this->assertSame(2, $clearedCount);
    }
}
