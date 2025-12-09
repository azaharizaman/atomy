<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Contract;

use Nexus\Procurement\Contracts\BlanketPurchaseOrderInterface;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderPersistInterface;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderQueryInterface;
use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\Services\Contract\ContractSpendTracker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(ContractSpendTracker::class)]
final class ContractSpendTrackerTest extends TestCase
{
    private BlanketPurchaseOrderQueryInterface&MockObject $queryMock;
    private BlanketPurchaseOrderPersistInterface&MockObject $persistMock;
    private ContractSpendTracker $tracker;

    protected function setUp(): void
    {
        $this->queryMock = $this->createMock(BlanketPurchaseOrderQueryInterface::class);
        $this->persistMock = $this->createMock(BlanketPurchaseOrderPersistInterface::class);
        $this->tracker = new ContractSpendTracker(
            $this->queryMock,
            $this->persistMock,
            new NullLogger()
        );
    }

    #[Test]
    public function getSpendContext_returns_context_for_existing_blanket_po(): void
    {
        $blanketPoId = 'bpo-123';
        $blanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $blanketPo->method('getId')->willReturn($blanketPoId);
        $blanketPo->method('getMaxAmountCents')->willReturn(100_000_00);
        $blanketPo->method('getCurrentSpendCents')->willReturn(25_000_00);
        $blanketPo->method('getMinOrderAmountCents')->willReturn(100_00);
        $blanketPo->method('getWarningThresholdPercent')->willReturn(80);

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn($blanketPo);

        $context = $this->tracker->getSpendContext($blanketPoId);

        $this->assertInstanceOf(ContractSpendContext::class, $context);
        $this->assertSame($blanketPoId, $context->blanketPoId);
        $this->assertSame(100_000_00, $context->maxAmountCents);
        $this->assertSame(25_000_00, $context->currentSpendCents);
        $this->assertSame(100_00, $context->minOrderAmountCents);
        $this->assertSame(80, $context->warningThresholdPercent);
    }

    #[Test]
    public function getSpendContext_returns_null_for_nonexistent_blanket_po(): void
    {
        $blanketPoId = 'bpo-nonexistent';

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn(null);

        $context = $this->tracker->getSpendContext($blanketPoId);

        $this->assertNull($context);
    }

    #[Test]
    public function recordSpend_updates_blanket_po_spend(): void
    {
        $blanketPoId = 'bpo-123';
        $amountCents = 5_000_00;
        $releaseOrderId = 'ro-456';

        $blanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $blanketPo->method('getId')->willReturn($blanketPoId);
        $blanketPo->method('getCurrentSpendCents')->willReturn(25_000_00);

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn($blanketPo);

        $blanketPo->expects($this->once())
            ->method('addSpend')
            ->with($amountCents, $releaseOrderId);

        $this->persistMock
            ->expects($this->once())
            ->method('update')
            ->with($blanketPo);

        $newTotal = $this->tracker->recordSpend($blanketPoId, $amountCents, $releaseOrderId);

        // After adding 5000 to 25000, total is 30000
        $this->assertSame(30_000_00, $newTotal);
    }

    #[Test]
    public function recordSpend_throws_for_nonexistent_blanket_po(): void
    {
        $blanketPoId = 'bpo-nonexistent';

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Blanket PO {$blanketPoId} not found");

        $this->tracker->recordSpend($blanketPoId, 1000_00, 'ro-123');
    }

    #[Test]
    public function reverseSpend_reduces_blanket_po_spend(): void
    {
        $blanketPoId = 'bpo-123';
        $amountCents = 5_000_00;
        $releaseOrderId = 'ro-456';

        $blanketPo = $this->createMock(BlanketPurchaseOrderInterface::class);
        $blanketPo->method('getId')->willReturn($blanketPoId);
        $blanketPo->method('getCurrentSpendCents')->willReturn(25_000_00);

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn($blanketPo);

        $blanketPo->expects($this->once())
            ->method('reverseSpend')
            ->with($amountCents, $releaseOrderId);

        $this->persistMock
            ->expects($this->once())
            ->method('update')
            ->with($blanketPo);

        $newTotal = $this->tracker->reverseSpend($blanketPoId, $amountCents, $releaseOrderId);

        // After reversing 5000 from 25000, total is 20000
        $this->assertSame(20_000_00, $newTotal);
    }

    #[Test]
    public function reverseSpend_throws_for_nonexistent_blanket_po(): void
    {
        $blanketPoId = 'bpo-nonexistent';

        $this->queryMock
            ->expects($this->once())
            ->method('findById')
            ->with($blanketPoId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Blanket PO {$blanketPoId} not found");

        $this->tracker->reverseSpend($blanketPoId, 1000_00, 'ro-123');
    }
}
