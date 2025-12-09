<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderInterface;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderPersistInterface;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderQueryInterface;
use Nexus\ProcurementOperations\Coordinators\BlanketPurchaseOrderCoordinator;
use Nexus\ProcurementOperations\DTOs\BlanketPOResult;
use Nexus\ProcurementOperations\DTOs\BlanketPurchaseOrderRequest;
use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\Events\BlanketPOCreatedEvent;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(BlanketPurchaseOrderCoordinator::class)]
final class BlanketPurchaseOrderCoordinatorTest extends TestCase
{
    private BlanketPurchaseOrderQueryInterface&MockObject $queryMock;
    private BlanketPurchaseOrderPersistInterface&MockObject $persistMock;
    private SequencingManagerInterface&MockObject $sequencingMock;
    private EventDispatcherInterface&MockObject $eventDispatcherMock;
    private AuditLogManagerInterface&MockObject $auditLoggerMock;
    private BlanketPurchaseOrderCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->queryMock = $this->createMock(BlanketPurchaseOrderQueryInterface::class);
        $this->persistMock = $this->createMock(BlanketPurchaseOrderPersistInterface::class);
        $this->sequencingMock = $this->createMock(SequencingManagerInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->auditLoggerMock = $this->createMock(AuditLogManagerInterface::class);

        $this->coordinator = new BlanketPurchaseOrderCoordinator(
            blanketPoQuery: $this->queryMock,
            blanketPoPersist: $this->persistMock,
            sequencing: $this->sequencingMock,
            eventDispatcher: $this->eventDispatcherMock,
            auditLogger: $this->auditLoggerMock,
            logger: new NullLogger()
        );
    }

    #[Test]
    public function create_creates_blanket_po_with_generated_number(): void
    {
        $request = new BlanketPurchaseOrderRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            requesterId: 'user-1',
            maxAmountCents: 100_000_00,
            currency: 'USD',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
            effectiveTo: new \DateTimeImmutable('2024-12-31'),
            description: 'Annual supply agreement'
        );

        $this->sequencingMock
            ->expects($this->once())
            ->method('getNext')
            ->with('blanket_purchase_order')
            ->willReturn('BPO-2024-001');

        $createdBlanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $createdBlanketPo->method('getId')->willReturn('bpo-123');
        $createdBlanketPo->method('getNumber')->willReturn('BPO-2024-001');

        $this->persistMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($createdBlanketPo);

        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(BlanketPOCreatedEvent::class));

        $this->auditLoggerMock
            ->expects($this->once())
            ->method('log');

        $result = $this->coordinator->create($request);

        $this->assertInstanceOf(BlanketPOResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('bpo-123', $result->blanketPoId);
        $this->assertSame('BPO-2024-001', $result->blanketPoNumber);
    }

    #[Test]
    public function create_fails_for_invalid_request(): void
    {
        $request = new BlanketPurchaseOrderRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            requesterId: 'user-1',
            maxAmountCents: -100, // Invalid: negative amount
            currency: 'USD',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
            effectiveTo: new \DateTimeImmutable('2024-12-31')
        );

        $this->persistMock
            ->expects($this->never())
            ->method('create');

        $result = $this->coordinator->create($request);

        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->errorMessage);
    }

    #[Test]
    public function getSpendStatus_returns_spend_context(): void
    {
        $blanketPoId = 'bpo-123';
        $blanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $blanketPo->method('getId')->willReturn($blanketPoId);
        $blanketPo->method('getMaxAmountCents')->willReturn(100_000_00);
        $blanketPo->method('getCurrentSpendCents')->willReturn(75_000_00);
        $blanketPo->method('getMinOrderAmountCents')->willReturn(100_00);
        $blanketPo->method('getWarningThresholdPercent')->willReturn(80);

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn($blanketPo);

        $context = $this->coordinator->getSpendStatus($blanketPoId);

        $this->assertInstanceOf(ContractSpendContext::class, $context);
        $this->assertSame(100_000_00, $context->maxAmountCents);
        $this->assertSame(75_000_00, $context->currentSpendCents);
        $this->assertSame(75.0, $context->getPercentUtilized());
    }

    #[Test]
    public function getSpendStatus_returns_null_for_nonexistent_blanket_po(): void
    {
        $blanketPoId = 'bpo-nonexistent';

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn(null);

        $context = $this->coordinator->getSpendStatus($blanketPoId);

        $this->assertNull($context);
    }

    #[Test]
    public function activate_changes_status_to_active(): void
    {
        $blanketPoId = 'bpo-123';
        $blanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $blanketPo->method('getId')->willReturn($blanketPoId);

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn($blanketPo);

        $blanketPo->expects($this->once())
            ->method('activate');

        $this->persistMock
            ->expects($this->once())
            ->method('update')
            ->with($blanketPo);

        $this->auditLoggerMock
            ->expects($this->once())
            ->method('log');

        $this->coordinator->activate($blanketPoId, 'user-1');
    }

    #[Test]
    public function activate_throws_for_nonexistent_blanket_po(): void
    {
        $blanketPoId = 'bpo-nonexistent';

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Blanket PO {$blanketPoId} not found");

        $this->coordinator->activate($blanketPoId, 'user-1');
    }

    #[Test]
    public function close_changes_status_to_closed(): void
    {
        $blanketPoId = 'bpo-123';
        $blanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $blanketPo->method('getId')->willReturn($blanketPoId);

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn($blanketPo);

        $blanketPo->expects($this->once())
            ->method('close')
            ->with('Contract period ended');

        $this->persistMock
            ->expects($this->once())
            ->method('update')
            ->with($blanketPo);

        $this->auditLoggerMock
            ->expects($this->once())
            ->method('log');

        $this->coordinator->close($blanketPoId, 'user-1', 'Contract period ended');
    }
}
