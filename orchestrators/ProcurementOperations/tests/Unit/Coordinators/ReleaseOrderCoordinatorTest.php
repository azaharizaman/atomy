<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Procurement\Contracts\ReleaseOrderInterface;
use Nexus\Procurement\Contracts\ReleaseOrderPersistInterface;
use Nexus\Procurement\Contracts\ReleaseOrderQueryInterface;
use Nexus\ProcurementOperations\Contracts\ContractSpendTrackerInterface;
use Nexus\ProcurementOperations\Coordinators\ReleaseOrderCoordinator;
use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\DTOs\ReleaseOrderRequest;
use Nexus\ProcurementOperations\Events\ContractSpendLimitWarningEvent;
use Nexus\ProcurementOperations\Events\ReleaseOrderCreatedEvent;
use Nexus\ProcurementOperations\Rules\Contract\ContractActiveRule;
use Nexus\ProcurementOperations\Rules\Contract\ContractEffectiveDateRule;
use Nexus\ProcurementOperations\Rules\Contract\ContractSpendLimitRule;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(ReleaseOrderCoordinator::class)]
final class ReleaseOrderCoordinatorTest extends TestCase
{
    private ReleaseOrderQueryInterface&MockObject $roQueryMock;
    private ReleaseOrderPersistInterface&MockObject $roPersistMock;
    private ContractSpendTrackerInterface&MockObject $spendTrackerMock;
    private SequencingManagerInterface&MockObject $sequencingMock;
    private EventDispatcherInterface&MockObject $eventDispatcherMock;
    private AuditLogManagerInterface&MockObject $auditLoggerMock;
    private ContractActiveRule&MockObject $activeRuleMock;
    private ContractSpendLimitRule&MockObject $spendLimitRuleMock;
    private ContractEffectiveDateRule&MockObject $effectiveDateRuleMock;
    private ReleaseOrderCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->roQueryMock = $this->createMock(ReleaseOrderQueryInterface::class);
        $this->roPersistMock = $this->createMock(ReleaseOrderPersistInterface::class);
        $this->spendTrackerMock = $this->createMock(ContractSpendTrackerInterface::class);
        $this->sequencingMock = $this->createMock(SequencingManagerInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->auditLoggerMock = $this->createMock(AuditLogManagerInterface::class);
        $this->activeRuleMock = $this->createMock(ContractActiveRule::class);
        $this->spendLimitRuleMock = $this->createMock(ContractSpendLimitRule::class);
        $this->effectiveDateRuleMock = $this->createMock(ContractEffectiveDateRule::class);

        $this->coordinator = new ReleaseOrderCoordinator(
            releaseOrderQuery: $this->roQueryMock,
            releaseOrderPersist: $this->roPersistMock,
            spendTracker: $this->spendTrackerMock,
            sequencing: $this->sequencingMock,
            eventDispatcher: $this->eventDispatcherMock,
            auditLogger: $this->auditLoggerMock,
            activeRule: $this->activeRuleMock,
            spendLimitRule: $this->spendLimitRuleMock,
            effectiveDateRule: $this->effectiveDateRuleMock,
            logger: new NullLogger()
        );
    }

    private function createValidRequest(): ReleaseOrderRequest
    {
        return new ReleaseOrderRequest(
            tenantId: 'tenant-1',
            blanketPoId: 'bpo-123',
            requesterId: 'user-1',
            lineItems: [
                ['productId' => 'prod-1', 'quantity' => 10, 'unitPriceCents' => 100_00],
                ['productId' => 'prod-2', 'quantity' => 5, 'unitPriceCents' => 200_00],
            ],
            deliveryDate: new \DateTimeImmutable('+7 days'),
            deliveryAddress: '123 Main St'
        );
    }

    private function createSpendContext(
        int $maxAmountCents = 100_000_00,
        int $currentSpendCents = 25_000_00,
        int $warningThresholdPercent = 80,
        ?int $minOrderAmountCents = null
    ): ContractSpendContext {
        return new ContractSpendContext(
            blanketPoId: 'bpo-123',
            maxAmountCents: $maxAmountCents,
            currentSpendCents: $currentSpendCents,
            warningThresholdPercent: $warningThresholdPercent,
            minOrderAmountCents: $minOrderAmountCents
        );
    }

    #[Test]
    public function create_creates_release_order_when_all_rules_pass(): void
    {
        $request = $this->createValidRequest();
        $totalCents = 2_000_00; // (10 * 100) + (5 * 200) = 1000 + 1000

        $context = $this->createSpendContext();

        $this->spendTrackerMock
            ->method('getSpendContext')
            ->with('bpo-123')
            ->willReturn($context);

        // All rules pass
        $this->activeRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );
        $this->spendLimitRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );
        $this->effectiveDateRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );

        $this->sequencingMock
            ->method('getNext')
            ->with('release_order')
            ->willReturn('RO-2024-001');

        $createdRo = $this->createMock(ReleaseOrderInterface::class);
        $createdRo->method('getId')->willReturn('ro-456');
        $createdRo->method('getNumber')->willReturn('RO-2024-001');
        $createdRo->method('getTotalAmountCents')->willReturn($totalCents);

        $this->roPersistMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($createdRo);

        $this->spendTrackerMock
            ->expects($this->once())
            ->method('recordSpend')
            ->with('bpo-123', $totalCents, 'ro-456')
            ->willReturn(27_000_00);

        // Expect ReleaseOrderCreatedEvent dispatch
        $this->eventDispatcherMock
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(ReleaseOrderCreatedEvent::class));

        $this->auditLoggerMock
            ->expects($this->once())
            ->method('log');

        $result = $this->coordinator->create($request);

        $this->assertTrue($result->success);
        $this->assertSame('ro-456', $result->releaseOrderId);
        $this->assertSame('RO-2024-001', $result->releaseOrderNumber);
    }

    #[Test]
    public function create_fails_when_blanket_po_not_found(): void
    {
        $request = $this->createValidRequest();

        $this->spendTrackerMock
            ->method('getSpendContext')
            ->with('bpo-123')
            ->willReturn(null);

        $this->roPersistMock
            ->expects($this->never())
            ->method('create');

        $result = $this->coordinator->create($request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Blanket PO not found', $result->errorMessage);
    }

    #[Test]
    public function create_fails_when_contract_not_active(): void
    {
        $request = $this->createValidRequest();
        $context = $this->createSpendContext();

        $this->spendTrackerMock
            ->method('getSpendContext')
            ->willReturn($context);

        // Contract not active
        $this->activeRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::fail('Contract is not active')
        );

        $this->roPersistMock
            ->expects($this->never())
            ->method('create');

        $result = $this->coordinator->create($request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Contract is not active', $result->errorMessage);
    }

    #[Test]
    public function create_fails_when_spend_limit_exceeded(): void
    {
        $request = $this->createValidRequest();
        $context = $this->createSpendContext();

        $this->spendTrackerMock
            ->method('getSpendContext')
            ->willReturn($context);

        $this->activeRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );

        // Spend limit exceeded
        $this->spendLimitRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::fail('Amount exceeds remaining contract balance')
        );

        $this->roPersistMock
            ->expects($this->never())
            ->method('create');

        $result = $this->coordinator->create($request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Amount exceeds remaining contract balance', $result->errorMessage);
    }

    #[Test]
    public function create_fails_when_outside_effective_dates(): void
    {
        $request = $this->createValidRequest();
        $context = $this->createSpendContext();

        $this->spendTrackerMock
            ->method('getSpendContext')
            ->willReturn($context);

        $this->activeRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );
        $this->spendLimitRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );

        // Outside effective dates
        $this->effectiveDateRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::fail('Order date is outside contract effective period')
        );

        $this->roPersistMock
            ->expects($this->never())
            ->method('create');

        $result = $this->coordinator->create($request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('outside contract effective period', $result->errorMessage);
    }

    #[Test]
    public function create_dispatches_warning_when_threshold_exceeded(): void
    {
        $request = $this->createValidRequest();
        $totalCents = 2_000_00;

        // Context where current spend + new order exceeds 80% threshold
        $context = $this->createSpendContext(
            maxAmountCents: 10_000_00,
            currentSpendCents: 7_000_00, // 70% utilized
            warningThresholdPercent: 80
        );
        // After this order: (7000 + 2000) / 10000 = 90% > 80%

        $this->spendTrackerMock
            ->method('getSpendContext')
            ->willReturn($context);

        $this->activeRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );
        $this->spendLimitRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );
        $this->effectiveDateRuleMock->method('check')->willReturn(
            \Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult::pass()
        );

        $this->sequencingMock->method('getNext')->willReturn('RO-2024-001');

        $createdRo = $this->createMock(ReleaseOrderInterface::class);
        $createdRo->method('getId')->willReturn('ro-456');
        $createdRo->method('getNumber')->willReturn('RO-2024-001');
        $createdRo->method('getTotalAmountCents')->willReturn($totalCents);

        $this->roPersistMock->method('create')->willReturn($createdRo);
        $this->spendTrackerMock->method('recordSpend')->willReturn(9_000_00);

        // Expect both ReleaseOrderCreatedEvent and ContractSpendLimitWarningEvent
        $warningEventDispatched = false;
        $this->eventDispatcherMock
            ->expects($this->atLeast(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$warningEventDispatched) {
                if ($event instanceof ContractSpendLimitWarningEvent) {
                    $warningEventDispatched = true;
                }
                return $event;
            });

        $result = $this->coordinator->create($request);

        $this->assertTrue($result->success);
        $this->assertTrue($warningEventDispatched, 'ContractSpendLimitWarningEvent should have been dispatched');
    }

    #[Test]
    public function cancel_reverses_spend_and_cancels_release_order(): void
    {
        $releaseOrderId = 'ro-456';
        $blanketPoId = 'bpo-123';
        $totalCents = 2_000_00;

        $releaseOrder = $this->createMock(ReleaseOrderInterface::class);
        $releaseOrder->method('getId')->willReturn($releaseOrderId);
        $releaseOrder->method('getBlanketPoId')->willReturn($blanketPoId);
        $releaseOrder->method('getTotalAmountCents')->willReturn($totalCents);
        $releaseOrder->method('isCancellable')->willReturn(true);

        $this->roQueryMock
            ->expects($this->once())
            ->method('findById')
            ->with($releaseOrderId)
            ->willReturn($releaseOrder);

        $releaseOrder->expects($this->once())
            ->method('cancel')
            ->with('No longer needed');

        $this->roPersistMock
            ->expects($this->once())
            ->method('update')
            ->with($releaseOrder);

        $this->spendTrackerMock
            ->expects($this->once())
            ->method('reverseSpend')
            ->with($blanketPoId, $totalCents, $releaseOrderId);

        $this->auditLoggerMock
            ->expects($this->once())
            ->method('log');

        $this->coordinator->cancel($releaseOrderId, 'user-1', 'No longer needed');
    }

    #[Test]
    public function cancel_throws_when_release_order_not_found(): void
    {
        $releaseOrderId = 'ro-nonexistent';

        $this->roQueryMock
            ->expects($this->once())
            ->method('findById')
            ->with($releaseOrderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Release Order {$releaseOrderId} not found");

        $this->coordinator->cancel($releaseOrderId, 'user-1', 'No longer needed');
    }

    #[Test]
    public function cancel_throws_when_release_order_not_cancellable(): void
    {
        $releaseOrderId = 'ro-456';

        $releaseOrder = $this->createMock(ReleaseOrderInterface::class);
        $releaseOrder->method('getId')->willReturn($releaseOrderId);
        $releaseOrder->method('isCancellable')->willReturn(false);

        $this->roQueryMock
            ->expects($this->once())
            ->method('findById')
            ->with($releaseOrderId)
            ->willReturn($releaseOrder);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Release Order {$releaseOrderId} cannot be cancelled in its current state");

        $this->coordinator->cancel($releaseOrderId, 'user-1', 'No longer needed');
    }
}
