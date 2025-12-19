<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\DisbursementInterface;
use Nexus\Payment\Contracts\DisbursementPersistInterface;
use Nexus\Payment\Contracts\DisbursementQueryInterface;
use Nexus\Payment\Contracts\PaymentExecutorInterface;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Events\DisbursementApprovedEvent;
use Nexus\Payment\Events\DisbursementCancelledEvent;
use Nexus\Payment\Events\DisbursementCompletedEvent;
use Nexus\Payment\Events\DisbursementCreatedEvent;
use Nexus\Payment\Events\DisbursementRejectedEvent;
use Nexus\Payment\Exceptions\DisbursementNotFoundException;
use Nexus\Payment\Exceptions\InvalidDisbursementStatusException;
use Nexus\Payment\Exceptions\PaymentValidationException;
use Nexus\Payment\Services\DisbursementManager;
use Nexus\Payment\Services\PaymentValidator;
use Nexus\Payment\ValueObjects\RecipientInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(DisbursementManager::class)]
final class DisbursementManagerTest extends TestCase
{
    private DisbursementQueryInterface&MockObject $disbursementQuery;
    private DisbursementPersistInterface&MockObject $disbursementPersist;
    private PaymentValidator $validator;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private PaymentExecutorInterface&MockObject $executor;
    private DisbursementManager $manager;

    protected function setUp(): void
    {
        $this->disbursementQuery = $this->createMock(DisbursementQueryInterface::class);
        $this->disbursementPersist = $this->createMock(DisbursementPersistInterface::class);
        $this->validator = new PaymentValidator(); // Use real instance (no constructor params)
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->executor = $this->createMock(PaymentExecutorInterface::class);

        $this->manager = new DisbursementManager(
            disbursementQuery: $this->disbursementQuery,
            disbursementPersist: $this->disbursementPersist,
            validator: $this->validator,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            defaultExecutor: $this->executor,
        );
    }

    #[Test]
    public function it_creates_disbursement(): void
    {
        $amount = Money::of(1000, 'MYR');
        $recipient = new RecipientInfo(
            id: 'vendor_abc',
            name: 'Vendor ABC',
            accountId: '1234567890',
        );

        $this->disbursementPersist->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(DisbursementInterface::class));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DisbursementCreatedEvent::class));

        $disbursement = $this->manager->create(
            tenantId: 'tenant_123',
            amount: $amount,
            recipient: $recipient,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user_123',
        );

        self::assertInstanceOf(DisbursementInterface::class, $disbursement);
        self::assertSame('tenant_123', $disbursement->getTenantId());
        self::assertTrue($disbursement->getAmount()->equals($amount));
        self::assertSame(DisbursementStatus::DRAFT, $disbursement->getStatus());
        self::assertStringStartsWith('DISB-', $disbursement->getReferenceNumber());
    }

    #[Test]
    public function it_creates_disbursement_with_source_documents(): void
    {
        $amount = Money::of(500, 'MYR');
        $recipient = new RecipientInfo(id: 'vendor_1', name: 'Vendor', accountId: '123456');
        $sourceDocIds = ['bill_001', 'bill_002'];

        $this->disbursementPersist->expects(self::once())->method('save');
        $this->eventDispatcher->expects(self::once())->method('dispatch');

        $disbursement = $this->manager->create(
            tenantId: 'tenant_123',
            amount: $amount,
            recipient: $recipient,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user_123',
            sourceDocumentIds: $sourceDocIds,
        );

        self::assertSame($sourceDocIds, $disbursement->getSourceDocumentIds());
    }

    #[Test]
    public function it_creates_scheduled_disbursement(): void
    {
        $amount = Money::of(1000, 'MYR');
        $recipient = new RecipientInfo('Vendor', '123456', 'BANK');
        $scheduledDate = new \DateTimeImmutable('+7 days');

        $this->disbursementPersist->expects(self::once())->method('save');
        $this->eventDispatcher->expects(self::once())->method('dispatch');

        $disbursement = $this->manager->create(
            tenantId: 'tenant_123',
            amount: $amount,
            recipient: $recipient,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user_123',
            scheduledDate: $scheduledDate,
        );

        self::assertInstanceOf(\DateTimeImmutable::class, $disbursement->getScheduledDate());
    }

    #[Test]
    public function it_submits_draft_for_approval(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::DRAFT);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())
            ->method('submitForApproval');

        $this->disbursementPersist->expects(self::once())->method('save');

        $result = $this->manager->submitForApproval('disb_123');

        self::assertInstanceOf(DisbursementInterface::class, $result);
    }

    #[Test]
    public function it_throws_when_submitting_non_draft(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::PENDING_APPROVAL);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->submitForApproval('disb_123');
    }

    #[Test]
    public function it_approves_pending_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::PENDING_APPROVAL);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())
            ->method('approve')
            ->with('approver_123', 'Looks good');

        $disbursement->method('getApprovedAt')->willReturn(new \DateTimeImmutable());

        $this->disbursementPersist->expects(self::once())->method('save');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DisbursementApprovedEvent::class));

        $result = $this->manager->approve('disb_123', 'approver_123', 'Looks good');

        self::assertInstanceOf(DisbursementInterface::class, $result);
    }

    #[Test]
    public function it_throws_when_approving_non_pending(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::DRAFT);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->approve('disb_123', 'approver_123');
    }

    #[Test]
    public function it_rejects_pending_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::PENDING_APPROVAL);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())
            ->method('reject')
            ->with('rejector_123', 'Missing documentation');

        $this->disbursementPersist->expects(self::once())->method('save');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DisbursementRejectedEvent::class));

        $result = $this->manager->reject('disb_123', 'rejector_123', 'Missing documentation');

        self::assertInstanceOf(DisbursementInterface::class, $result);
    }

    #[Test]
    public function it_throws_when_rejecting_non_pending(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::APPROVED);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->reject('disb_123', 'rejector_123', 'Reason');
    }

    #[Test]
    public function it_processes_approved_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::APPROVED);
        $disbursement->method('getScheduledDate')->willReturn(null);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())->method('markAsProcessing');
        $disbursement->expects(self::once())->method('markAsCompleted');

        $this->disbursementPersist->expects(self::atLeast(2))->method('save');
        $this->disbursementPersist->expects(self::once())
            ->method('linkPaymentTransaction');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DisbursementCompletedEvent::class));

        $result = $this->manager->process('disb_123');

        self::assertInstanceOf(DisbursementInterface::class, $result);
    }

    #[Test]
    public function it_throws_when_processing_non_approved(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::PENDING_APPROVAL);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->process('disb_123');
    }

    #[Test]
    public function it_throws_when_processing_future_scheduled(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::APPROVED);
        $disbursement->method('getScheduledDate')->willReturn(new \DateTimeImmutable('+7 days'));

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('scheduled for');

        $this->manager->process('disb_123');
    }

    #[Test]
    public function it_cancels_draft_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::DRAFT);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())
            ->method('cancel')
            ->with('No longer needed');

        $this->disbursementPersist->expects(self::once())->method('save');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DisbursementCancelledEvent::class));

        $result = $this->manager->cancel('disb_123', 'user_123', 'No longer needed');

        self::assertInstanceOf(DisbursementInterface::class, $result);
    }

    #[Test]
    public function it_cancels_pending_approval_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::PENDING_APPROVAL);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())->method('cancel');
        $this->disbursementPersist->expects(self::once())->method('save');
        $this->eventDispatcher->expects(self::once())->method('dispatch');

        $this->manager->cancel('disb_123', 'user_123', 'Reason');
    }

    #[Test]
    public function it_cancels_approved_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::APPROVED);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())->method('cancel');
        $this->disbursementPersist->expects(self::once())->method('save');

        $this->manager->cancel('disb_123', 'user_123', 'Reason');
    }

    #[Test]
    public function it_throws_when_cancelling_completed(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::COMPLETED);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->cancel('disb_123', 'user_123', 'Reason');
    }

    #[Test]
    public function it_throws_when_cancelling_processing(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::PROCESSING);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->cancel('disb_123', 'user_123', 'Reason');
    }

    #[Test]
    public function it_schedules_disbursement(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::DRAFT);
        $scheduledDate = new \DateTimeImmutable('+14 days');

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $disbursement->expects(self::once())
            ->method('schedule')
            ->with($scheduledDate);

        $this->disbursementPersist->expects(self::once())->method('save');

        $result = $this->manager->schedule('disb_123', $scheduledDate);

        self::assertInstanceOf(DisbursementInterface::class, $result);
    }

    #[Test]
    public function it_throws_when_scheduling_with_past_date(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::DRAFT);
        $pastDate = new \DateTimeImmutable('-1 day');

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('must be in the future');

        $this->manager->schedule('disb_123', $pastDate);
    }

    #[Test]
    public function it_throws_when_scheduling_completed(): void
    {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getStatus')->willReturn(DisbursementStatus::COMPLETED);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $this->expectException(InvalidDisbursementStatusException::class);

        $this->manager->schedule('disb_123', new \DateTimeImmutable('+7 days'));
    }

    #[Test]
    public function it_gets_pending_approvals(): void
    {
        $disbursements = [
            $this->createMockDisbursement(DisbursementStatus::PENDING_APPROVAL),
            $this->createMockDisbursement(DisbursementStatus::PENDING_APPROVAL),
        ];

        $this->disbursementQuery->expects(self::once())
            ->method('findPendingApproval')
            ->with('tenant_123')
            ->willReturn($disbursements);

        $result = $this->manager->getPendingApprovals('tenant_123');

        self::assertCount(2, $result);
    }

    #[Test]
    public function it_gets_ready_for_processing(): void
    {
        $ready = $this->createMock(DisbursementInterface::class);
        $ready->method('getScheduledDate')->willReturn(null);
        $ready->method('getTenantId')->willReturn('tenant_123');

        $notReady = $this->createMock(DisbursementInterface::class);
        $notReady->method('getScheduledDate')->willReturn(new \DateTimeImmutable('+7 days'));
        $notReady->method('getTenantId')->willReturn('tenant_123');

        $pastScheduled = $this->createMock(DisbursementInterface::class);
        $pastScheduled->method('getScheduledDate')->willReturn(new \DateTimeImmutable('-1 day'));
        $pastScheduled->method('getTenantId')->willReturn('tenant_123');

        $otherTenant = $this->createMock(DisbursementInterface::class);
        $otherTenant->method('getScheduledDate')->willReturn(null);
        $otherTenant->method('getTenantId')->willReturn('other_tenant');

        $this->disbursementQuery->expects(self::once())
            ->method('findByStatus')
            ->with(DisbursementStatus::APPROVED)
            ->willReturn([$ready, $notReady, $pastScheduled, $otherTenant]);

        $result = $this->manager->getReadyForProcessing('tenant_123');

        self::assertCount(2, $result); // ready and pastScheduled (same tenant, not scheduled or past scheduled)
    }

    #[Test]
    public function it_gets_disbursement_status(): void
    {
        $disbursement = $this->createMockDisbursement(DisbursementStatus::APPROVED);

        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn($disbursement);

        $status = $this->manager->getStatus('disb_123');

        self::assertSame(DisbursementStatus::APPROVED, $status);
    }

    #[Test]
    public function it_finds_or_fails_when_not_found(): void
    {
        $this->disbursementQuery->expects(self::once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(DisbursementNotFoundException::class);

        $this->manager->findOrFail('disb_nonexistent');
    }

    /**
     * Create a mock disbursement.
     */
    private function createMockDisbursement(
        DisbursementStatus $status,
    ): DisbursementInterface&MockObject {
        $disbursement = $this->createMock(DisbursementInterface::class);
        $disbursement->method('getId')->willReturn('disb_123');
        $disbursement->method('getTenantId')->willReturn('tenant_123');
        $disbursement->method('getStatus')->willReturn($status);
        $disbursement->method('getAmount')->willReturn(Money::of(1000, 'MYR'));
        $disbursement->method('getReferenceNumber')->willReturn('DISB-20240101-ABC123');
        $disbursement->method('getRecipient')->willReturn(
            new RecipientInfo('Vendor', '123456', 'BANK')
        );

        return $disbursement;
    }
}
