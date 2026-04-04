<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Tests\Unit;

use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
use Nexus\Sourcing\Exceptions\UnsupportedRfqBulkActionException;
use Nexus\Sourcing\ValueObjects\RfqBulkAction;
use Nexus\SourcingOperations\Contracts\RfqInvitationPersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqInvitationQueryPortInterface;
use Nexus\SourcingOperations\Contracts\RfqInvitationReminderPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecyclePersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecycleQueryPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLineItemPersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLineItemQueryPortInterface;
use Nexus\SourcingOperations\Contracts\SourcingRfqStatusTransitionPolicyInterface;
use Nexus\SourcingOperations\Contracts\SourcingTransactionManagerInterface;
use Nexus\SourcingOperations\DTOs\ApplyRfqBulkActionCommand;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;
use Nexus\SourcingOperations\DTOs\RfqLifecycleOutcome;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Nexus\SourcingOperations\DTOs\RfqLineItemRecord;
use Nexus\SourcingOperations\DTOs\SaveRfqDraftCommand;
use Nexus\SourcingOperations\DTOs\TransitionRfqStatusCommand;
use Nexus\SourcingOperations\SourcingOperationsCoordinator;
use PHPUnit\Framework\TestCase;

final class RfqLifecycleCoordinatorTest extends TestCase
{
    private function createTransactionManagerStub(?array &$calls = null): SourcingTransactionManagerInterface
    {
        return new class($calls) implements SourcingTransactionManagerInterface {
            /**
             * @param array<int, string>|null $calls
             */
            public function __construct(private ?array &$calls)
            {
            }

            public function transaction(callable $callback): mixed
            {
                if ($this->calls !== null) {
                    $this->calls[] = 'begin';
                }

                try {
                    $result = $callback();

                    if ($this->calls !== null) {
                        $this->calls[] = 'commit';
                    }

                    return $result;
                } catch (\Throwable $throwable) {
                    if ($this->calls !== null) {
                        $this->calls[] = 'rollback';
                    }

                    throw $throwable;
                }
            }
        };
    }

    public function test_duplicate_uses_tenant_scoped_reads_and_copies_only_approved_data(): void
    {
        $tenantId = 'tenant-001';
        $source = new RfqLifecycleRecord(
            tenantId: $tenantId,
            rfqId: 'rfq-100',
            status: 'published',
            title: 'Server Refresh',
            projectId: 'proj-9',
            description: 'Replace aging servers',
            estimatedValue: 125000.0,
            savingsPercentage: 12.5,
            submissionDeadline: '2026-04-10T12:00:00Z',
            closingDate: '2026-04-12T12:00:00Z',
            expectedAwardAt: '2026-04-20T12:00:00Z',
            technicalReviewDueAt: '2026-04-14T12:00:00Z',
            financialReviewDueAt: '2026-04-15T12:00:00Z',
            paymentTerms: 'Net 45',
            evaluationMethod: 'weighted_score',
        );

        $lineItems = [
            new RfqLineItemRecord(
                id: 'line-1',
                description: 'Primary servers',
                quantity: 4.0,
                uom: 'EA',
                unitPrice: 25000.0,
                currency: 'USD',
                specifications: 'Redundant',
                sortOrder: 1,
            ),
            new RfqLineItemRecord(
                id: 'line-2',
                description: 'Support',
                quantity: 1.0,
                uom: 'LOT',
                unitPrice: 5000.0,
                currency: 'USD',
                specifications: null,
                sortOrder: 2,
            ),
        ];

        $query = new class($source) implements RfqLifecycleQueryPortInterface {
            public array $calls = [];

            public function __construct(private readonly RfqLifecycleRecord $source)
            {
            }

            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                $this->calls[] = [$tenantId, $rfqId];

                return $tenantId === $this->source->tenantId && $rfqId === $this->source->rfqId ? $this->source : null;
            }
        };

        $lineItemQuery = new class($lineItems) implements RfqLineItemQueryPortInterface {
            public array $calls = [];

            /**
             * @param array<int, RfqLineItemRecord> $lineItems
             */
            public function __construct(private readonly array $lineItems)
            {
            }

            /**
             * @return array<int, RfqLineItemRecord>
             */
            public function findByTenantAndRfqId(string $tenantId, string $rfqId): array
            {
                $this->calls[] = [$tenantId, $rfqId];

                return $tenantId === 'tenant-001' && $rfqId === 'rfq-100' ? $this->lineItems : [];
            }
        };

        $persist = new class implements RfqLifecyclePersistPortInterface {
            public array $duplicateCalls = [];

            public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
            {
                $this->duplicateCalls[] = [$sourceRfq, $command, $lineItems];

                return new RfqLifecycleRecord(
                    tenantId: $command->tenantId,
                    rfqId: 'rfq-200',
                    status: 'draft',
                    title: $sourceRfq->title,
                    projectId: $sourceRfq->projectId,
                    description: $sourceRfq->description,
                    estimatedValue: $sourceRfq->estimatedValue,
                    savingsPercentage: $sourceRfq->savingsPercentage,
                    submissionDeadline: $sourceRfq->submissionDeadline,
                    closingDate: $sourceRfq->closingDate,
                    expectedAwardAt: $sourceRfq->expectedAwardAt,
                    technicalReviewDueAt: $sourceRfq->technicalReviewDueAt,
                    financialReviewDueAt: $sourceRfq->financialReviewDueAt,
                    paymentTerms: $sourceRfq->paymentTerms,
                    evaluationMethod: $sourceRfq->evaluationMethod,
                );
            }

            public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected saveDraft call.');
            }

            public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected transitionStatus call.');
            }

            public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
            {
                throw new \LogicException('Unexpected applyBulkAction call.');
            }
        };

        $lineItemPersist = new class implements RfqLineItemPersistPortInterface {
            public array $calls = [];

            public function copyToRfq(string $tenantId, string $sourceRfqId, string $targetRfqId, array $lineItems): int
            {
                $this->calls[] = [$tenantId, $sourceRfqId, $targetRfqId, $lineItems];

                return count($lineItems);
            }
        };

        $transactionCalls = [];

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $persist,
            $lineItemQuery,
            $lineItemPersist,
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub($transactionCalls),
        );

        $result = $coordinator->duplicate(new DuplicateRfqCommand(
            tenantId: $tenantId,
            sourceRfqId: 'rfq-100',
        ));

        self::assertInstanceOf(RfqLifecycleOutcome::class, $result);
        self::assertSame('duplicate', $result->action);
        self::assertSame($tenantId, $result->tenantId);
        self::assertSame('rfq-200', $result->rfqId);
        self::assertSame('rfq-100', $result->sourceRfqId);
        self::assertSame('draft', $result->status);
        self::assertSame(1, $result->affectedCount);
        self::assertSame(2, $result->copiedLineItemCount);
        self::assertSame(0, $result->copiedChildRecordCount);

        self::assertSame([[$tenantId, 'rfq-100']], $query->calls);
        self::assertSame([[$tenantId, 'rfq-100']], $lineItemQuery->calls);
        self::assertCount(1, $persist->duplicateCalls);
        self::assertCount(1, $lineItemPersist->calls);
        self::assertSame(['begin', 'commit'], $transactionCalls);
    }

    public function test_save_draft_rejects_non_draft_rfq(): void
    {
        $query = new class implements RfqLifecycleQueryPortInterface {
            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return new RfqLifecycleRecord(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    status: 'published',
                    title: 'Server Refresh',
                );
            }
        };

        $persist = new class implements RfqLifecyclePersistPortInterface {
            public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected createDuplicate call.');
            }

            public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected saveDraft call.');
            }

            public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected transitionStatus call.');
            }

            public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
            {
                throw new \LogicException('Unexpected applyBulkAction call.');
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $persist,
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $this->expectException(RfqLifecyclePreconditionException::class);

        $coordinator->saveDraft(new SaveRfqDraftCommand(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            title: 'Server Refresh Draft',
        ));
    }

    public function test_save_draft_updates_only_editable_fields(): void
    {
        $query = new class implements RfqLifecycleQueryPortInterface {
            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return new RfqLifecycleRecord(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    status: 'draft',
                    title: 'Original title',
                    projectId: 'proj-1',
                    paymentTerms: 'Net 30',
                );
            }
        };

        $persist = new class implements RfqLifecyclePersistPortInterface {
            public array $draftCalls = [];

            public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected createDuplicate call.');
            }

            public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
            {
                $this->draftCalls[] = [$rfq, $command];

                return new RfqLifecycleRecord(
                    tenantId: $rfq->tenantId,
                    rfqId: $rfq->rfqId,
                    status: $rfq->status,
                    title: $rfq->title,
                    projectId: $rfq->projectId,
                    paymentTerms: $rfq->paymentTerms,
                );
            }

            public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected transitionStatus call.');
            }

            public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
            {
                throw new \LogicException('Unexpected applyBulkAction call.');
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $persist,
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $result = $coordinator->saveDraft(new SaveRfqDraftCommand(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            title: 'Updated title',
            projectId: 'proj-2',
            paymentTerms: 'Net 45',
            presentFields: ['title', 'project_id', 'payment_terms'],
        ));

        self::assertSame('save_draft', $result->action);
        self::assertSame('draft', $result->status);
        self::assertSame('rfq-100', $result->rfqId);
        self::assertSame(1, $result->affectedCount);
        self::assertCount(1, $persist->draftCalls);
        self::assertSame('draft', $persist->draftCalls[0][0]->status);
        self::assertSame('Updated title', $persist->draftCalls[0][0]->title);
        self::assertSame('proj-2', $persist->draftCalls[0][0]->projectId);
        self::assertSame('Net 45', $persist->draftCalls[0][0]->paymentTerms);
    }

    public function test_bulk_action_applies_allowlisted_actions_and_reports_affected_count(): void
    {
        $query = new class implements RfqLifecycleQueryPortInterface {
            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return new RfqLifecycleRecord(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    status: 'published',
                    title: 'RFQ ' . $rfqId,
                );
            }
        };

        $policy = $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class);
        $policyCalls = [];
        $policy->expects(self::exactly(2))
            ->method('assertTransitionAllowed')
            ->willReturnCallback(static function (string $fromStatus, string $toStatus) use (&$policyCalls): void {
                $policyCalls[] = [$fromStatus, $toStatus];
            });

        $persist = new class implements RfqLifecyclePersistPortInterface {
            public array $calls = [];

            public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected createDuplicate call.');
            }

            public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected saveDraft call.');
            }

            public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
            {
                $this->calls[] = [$rfq, $command];

                return new RfqLifecycleRecord(
                    tenantId: $rfq->tenantId,
                    rfqId: $rfq->rfqId,
                    status: $command->status,
                    title: $rfq->title,
                );
            }

            public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
            {
                throw new \LogicException('Unexpected applyBulkAction call.');
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $persist,
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $policy,
            $this->createTransactionManagerStub(),
        );

        $result = $coordinator->applyBulkAction(new ApplyRfqBulkActionCommand(
            tenantId: 'tenant-001',
            action: 'close',
            rfqIds: ['rfq-1', 'rfq-2'],
        ));

        self::assertSame('bulk_action', $result->action);
        self::assertSame('closed', $result->status);
        self::assertSame(2, $result->affectedCount);
        self::assertCount(2, $persist->calls);
        self::assertSame([
            ['published', 'closed'],
            ['published', 'closed'],
        ], $policyCalls);
    }

    public function test_bulk_action_rejects_unsupported_actions(): void
    {
        $coordinator = new SourcingOperationsCoordinator(
            $this->createMock(RfqLifecycleQueryPortInterface::class),
            $this->createMock(RfqLifecyclePersistPortInterface::class),
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $this->expectException(UnsupportedRfqBulkActionException::class);

        $coordinator->applyBulkAction(new ApplyRfqBulkActionCommand(
            tenantId: 'tenant-001',
            action: 'archive',
            rfqIds: ['rfq-1'],
        ));
    }

    public function test_transition_status_uses_shared_policy(): void
    {
        $query = new class implements RfqLifecycleQueryPortInterface {
            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return new RfqLifecycleRecord(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    status: 'draft',
                    title: 'RFQ ' . $rfqId,
                );
            }
        };

        $policy = $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class);
        $policy->expects(self::once())
            ->method('assertTransitionAllowed')
            ->with('draft', 'published');

        $persist = new class implements RfqLifecyclePersistPortInterface {
            public array $calls = [];

            public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected createDuplicate call.');
            }

            public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected saveDraft call.');
            }

            public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
            {
                $this->calls[] = [$rfq, $command];

                return new RfqLifecycleRecord(
                    tenantId: $rfq->tenantId,
                    rfqId: $rfq->rfqId,
                    status: $command->status,
                    title: $rfq->title,
                );
            }

            public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
            {
                throw new \LogicException('Unexpected applyBulkAction call.');
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $persist,
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $policy,
            $this->createTransactionManagerStub(),
        );

        $result = $coordinator->transitionStatus(new TransitionRfqStatusCommand(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            status: 'published',
        ));

        self::assertSame('transition_status', $result->action);
        self::assertSame('published', $result->status);
        self::assertSame('rfq-100', $result->rfqId);
        self::assertCount(1, $persist->calls);
    }

    public function test_remind_invitation_stays_tenant_scoped_and_returns_real_response(): void
    {
        $rfq = new RfqLifecycleRecord(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            status: 'published',
            title: 'Server Refresh',
        );

        $invitation = new RfqInvitationRecord(
            id: 'inv-1',
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            vendorEmail: 'vendor@example.com',
            vendorName: 'Vendor Co',
            status: 'pending',
            channel: 'email',
        );

        $query = new class($rfq, $invitation) implements RfqLifecycleQueryPortInterface, RfqInvitationQueryPortInterface {
            public array $rfqCalls = [];
            public array $invitationCalls = [];

            public function __construct(
                private readonly RfqLifecycleRecord $rfq,
                private readonly RfqInvitationRecord $invitation,
            ) {
            }

            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                $this->rfqCalls[] = [$tenantId, $rfqId];

                return $tenantId === $this->rfq->tenantId && $rfqId === $this->rfq->rfqId ? $this->rfq : null;
            }

            public function findInvitationByTenantAndId(string $tenantId, string $rfqId, string $invitationId): ?RfqInvitationRecord
            {
                $this->invitationCalls[] = [$tenantId, $rfqId, $invitationId];

                return $tenantId === $this->invitation->tenantId
                    && $rfqId === $this->invitation->rfqId
                    && $invitationId === $this->invitation->id
                    ? $this->invitation
                    : null;
            }
        };

        $persist = new class implements RfqInvitationPersistPortInterface {
            public array $calls = [];

            public function markInvitationReminded(RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): RfqInvitationRecord
            {
                $this->calls[] = [$invitation, $command];

                return new RfqInvitationRecord(
                    id: $invitation->id,
                    tenantId: $invitation->tenantId,
                    rfqId: $invitation->rfqId,
                    vendorEmail: $invitation->vendorEmail,
                    vendorName: $invitation->vendorName,
                    status: $invitation->status,
                    channel: $invitation->channel,
                );
            }
        };

        $reminder = new class implements RfqInvitationReminderPortInterface {
            public array $calls = [];

            public function sendReminder(RfqLifecycleRecord $rfq, RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): void
            {
                $this->calls[] = [$rfq, $invitation, $command];
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $this->createMock(RfqLifecyclePersistPortInterface::class),
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $query,
            $persist,
            $reminder,
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $result = $coordinator->remindInvitation(new RemindRfqInvitationCommand(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            invitationId: 'inv-1',
            requestedByPrincipalId: 'user-7',
        ));

        self::assertSame('remind_invitation', $result->action);
        self::assertSame('pending', $result->status);
        self::assertSame('inv-1', $result->invitationId);
        self::assertSame('rfq-100', $result->rfqId);
        self::assertSame(1, $result->affectedCount);
        self::assertCount(1, $query->rfqCalls);
        self::assertCount(1, $query->invitationCalls);
        self::assertCount(1, $persist->calls);
        self::assertCount(1, $reminder->calls);
        self::assertSame($invitation->id, $persist->calls[0][0]->id);
        self::assertSame($invitation->id, $reminder->calls[0][1]->id);
    }

    public function test_remind_invitation_rejects_cross_tenant_resources(): void
    {
        $query = new class implements RfqLifecycleQueryPortInterface, RfqInvitationQueryPortInterface {
            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return null;
            }

            public function findInvitationByTenantAndId(string $tenantId, string $rfqId, string $invitationId): ?RfqInvitationRecord
            {
                return null;
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $this->createMock(RfqLifecyclePersistPortInterface::class),
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $query,
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $this->expectException(RfqLifecyclePreconditionException::class);

        $coordinator->remindInvitation(new RemindRfqInvitationCommand(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            invitationId: 'inv-1',
            requestedByPrincipalId: 'user-7',
        ));
    }

    public function test_save_draft_can_clear_nullable_fields_when_present_with_null(): void
    {
        $query = new class implements RfqLifecycleQueryPortInterface {
            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return new RfqLifecycleRecord(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    status: 'draft',
                    title: 'Original title',
                    projectId: 'proj-1',
                    description: 'Original description',
                    estimatedValue: 1200.0,
                    paymentTerms: 'Net 30',
                );
            }
        };

        $persist = new class implements RfqLifecyclePersistPortInterface {
            public ?RfqLifecycleRecord $savedRecord = null;

            public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected createDuplicate call.');
            }

            public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
            {
                $this->savedRecord = $rfq;

                return $rfq;
            }

            public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
            {
                throw new \LogicException('Unexpected transitionStatus call.');
            }

            public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
            {
                throw new \LogicException('Unexpected applyBulkAction call.');
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $persist,
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $coordinator->saveDraft(new SaveRfqDraftCommand(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            projectId: null,
            description: null,
            estimatedValue: null,
            paymentTerms: null,
            presentFields: ['project_id', 'description', 'estimated_value', 'payment_terms'],
        ));

        self::assertNotNull($persist->savedRecord);
        self::assertNull($persist->savedRecord?->projectId);
        self::assertNull($persist->savedRecord?->description);
        self::assertNull($persist->savedRecord?->estimatedValue);
        self::assertNull($persist->savedRecord?->paymentTerms);
        self::assertSame('Original title', $persist->savedRecord?->title);
    }

    public function test_bulk_action_rejects_preloaded_records_that_do_not_match_requested_ids(): void
    {
        $policy = $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class);
        $policy->expects(self::once())
            ->method('assertTransitionAllowed')
            ->with('published', 'closed');

        $persist = $this->createMock(RfqLifecyclePersistPortInterface::class);
        $persist->expects(self::never())->method('applyBulkAction');

        $coordinator = new SourcingOperationsCoordinator(
            $this->createMock(RfqLifecycleQueryPortInterface::class),
            $persist,
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $this->createMock(RfqInvitationQueryPortInterface::class),
            $this->createMock(RfqInvitationPersistPortInterface::class),
            $this->createMock(RfqInvitationReminderPortInterface::class),
            $policy,
            $this->createTransactionManagerStub(),
        );

        $this->expectException(RfqLifecyclePreconditionException::class);

        $coordinator->applyBulkAction(
            new ApplyRfqBulkActionCommand(
                tenantId: 'tenant-001',
                action: 'close',
                rfqIds: ['rfq-1', 'rfq-2'],
            ),
            [
                new RfqLifecycleRecord(
                    tenantId: 'tenant-001',
                    rfqId: 'rfq-1',
                    status: 'published',
                    title: 'RFQ 1',
                ),
            ],
        );
    }

    public function test_remind_invitation_persists_only_after_successful_delivery(): void
    {
        $rfq = new RfqLifecycleRecord(
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            status: 'published',
            title: 'Server Refresh',
        );
        $invitation = new RfqInvitationRecord(
            id: 'inv-1',
            tenantId: 'tenant-001',
            rfqId: 'rfq-100',
            vendorEmail: 'vendor@example.com',
            vendorName: 'Vendor Co',
            status: 'pending',
            channel: 'email',
        );

        $query = new class($rfq, $invitation) implements RfqLifecycleQueryPortInterface, RfqInvitationQueryPortInterface {
            public function __construct(
                private readonly RfqLifecycleRecord $rfq,
                private readonly RfqInvitationRecord $invitation,
            ) {
            }

            public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
            {
                return $this->rfq;
            }

            public function findInvitationByTenantAndId(string $tenantId, string $rfqId, string $invitationId): ?RfqInvitationRecord
            {
                return $this->invitation;
            }
        };

        $persist = new class implements RfqInvitationPersistPortInterface {
            public int $calls = 0;

            public function markInvitationReminded(RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): RfqInvitationRecord
            {
                ++$this->calls;

                return $invitation;
            }
        };

        $reminder = new class implements RfqInvitationReminderPortInterface {
            public function sendReminder(RfqLifecycleRecord $rfq, RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): void
            {
                throw new \RuntimeException('Delivery failed.');
            }
        };

        $coordinator = new SourcingOperationsCoordinator(
            $query,
            $this->createMock(RfqLifecyclePersistPortInterface::class),
            $this->createMock(RfqLineItemQueryPortInterface::class),
            $this->createMock(RfqLineItemPersistPortInterface::class),
            $query,
            $persist,
            $reminder,
            $this->createMock(SourcingRfqStatusTransitionPolicyInterface::class),
            $this->createTransactionManagerStub(),
        );

        $this->expectException(\RuntimeException::class);

        try {
            $coordinator->remindInvitation(new RemindRfqInvitationCommand(
                tenantId: 'tenant-001',
                rfqId: 'rfq-100',
                invitationId: 'inv-1',
                requestedByPrincipalId: 'user-7',
            ));
        } finally {
            self::assertSame(0, $persist->calls);
        }
    }
}
