<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations;

use Nexus\Sourcing\Contracts\RfqStatusTransitionPolicyInterface;
use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
use Nexus\Sourcing\Exceptions\UnsupportedRfqBulkActionException;
use Nexus\Sourcing\ValueObjects\RfqBulkAction;
use Nexus\Sourcing\ValueObjects\RfqStatus;
use Nexus\SourcingOperations\Contracts\RfqInvitationPersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqInvitationQueryPortInterface;
use Nexus\SourcingOperations\Contracts\RfqInvitationReminderPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecycleCoordinatorInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecyclePersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecycleQueryPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLineItemPersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLineItemQueryPortInterface;
use Nexus\SourcingOperations\DTOs\ApplyRfqBulkActionCommand;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\SourcingOperations\DTOs\RfqLifecycleOutcome;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Nexus\SourcingOperations\DTOs\SaveRfqDraftCommand;
use Nexus\SourcingOperations\DTOs\TransitionRfqStatusCommand;

/**
 * Coordinator for RFQ lifecycle orchestration.
 */
final readonly class SourcingOperationsCoordinator implements RfqLifecycleCoordinatorInterface
{
    public function __construct(
        private RfqLifecycleQueryPortInterface $rfqQuery,
        private RfqLifecyclePersistPortInterface $rfqPersist,
        private RfqLineItemQueryPortInterface $lineItemQuery,
        private RfqLineItemPersistPortInterface $lineItemPersist,
        private RfqInvitationQueryPortInterface $invitationQuery,
        private RfqInvitationPersistPortInterface $invitationPersist,
        private RfqInvitationReminderPortInterface $invitationReminder,
        private RfqStatusTransitionPolicyInterface $statusTransitionPolicy,
    ) {
    }

    /**
     * Duplicate the RFQ core record and line items.
     */
    public function duplicate(DuplicateRfqCommand $command): RfqLifecycleOutcome
    {
        $source = $this->loadRfq($command->tenantId, $command->sourceRfqId);
        $lineItems = $this->lineItemQuery->findByTenantAndRfqId($command->tenantId, $command->sourceRfqId);

        $duplicated = $this->rfqPersist->createDuplicate($source, $command, $lineItems);
        $copiedLineItemCount = $this->lineItemPersist->copyToRfq(
            $command->tenantId,
            $source->rfqId,
            $duplicated->rfqId,
            $lineItems,
        );

        return new RfqLifecycleOutcome(
            action: 'duplicate',
            tenantId: $command->tenantId,
            status: $duplicated->status,
            rfqId: $duplicated->rfqId,
            sourceRfqId: $source->rfqId,
            affectedCount: 1,
            copiedLineItemCount: $copiedLineItemCount,
        );
    }

    public function saveDraft(SaveRfqDraftCommand $command): RfqLifecycleOutcome
    {
        $rfq = $this->loadRfq($command->tenantId, $command->rfqId);

        if ($rfq->status !== RfqStatus::DRAFT) {
            throw RfqLifecyclePreconditionException::forRfq($rfq->rfqId, 'draft edits are only allowed while the RFQ is in draft.');
        }

        $updated = new RfqLifecycleRecord(
            tenantId: $rfq->tenantId,
            rfqId: $rfq->rfqId,
            status: $rfq->status,
            title: $command->title ?? $rfq->title,
            projectId: $command->projectId ?? $rfq->projectId,
            description: $command->description ?? $rfq->description,
            estimatedValue: $command->estimatedValue ?? $rfq->estimatedValue,
            savingsPercentage: $command->savingsPercentage ?? $rfq->savingsPercentage,
            submissionDeadline: $command->submissionDeadline ?? $rfq->submissionDeadline,
            closingDate: $command->closingDate ?? $rfq->closingDate,
            expectedAwardAt: $command->expectedAwardAt ?? $rfq->expectedAwardAt,
            technicalReviewDueAt: $command->technicalReviewDueAt ?? $rfq->technicalReviewDueAt,
            financialReviewDueAt: $command->financialReviewDueAt ?? $rfq->financialReviewDueAt,
            paymentTerms: $command->paymentTerms ?? $rfq->paymentTerms,
            evaluationMethod: $command->evaluationMethod ?? $rfq->evaluationMethod,
        );

        $saved = $this->rfqPersist->saveDraft($updated, $command);

        return new RfqLifecycleOutcome(
            action: 'save_draft',
            tenantId: $command->tenantId,
            status: $saved->status,
            rfqId: $saved->rfqId,
            affectedCount: 1,
        );
    }

    public function applyBulkAction(ApplyRfqBulkActionCommand $command, ?array $records = null): RfqLifecycleOutcome
    {
        $bulkAction = RfqBulkAction::fromString($command->action);
        $targetStatus = match ($bulkAction->value()) {
            'close' => RfqStatus::CLOSED,
            'cancel' => RfqStatus::CANCELLED,
            default => throw UnsupportedRfqBulkActionException::fromAction($command->action, ['close', 'cancel']),
        };

        if ($records === null) {
            $affected = 0;
            foreach ($command->rfqIds as $rfqId) {
                $rfq = $this->loadRfq($command->tenantId, $rfqId);
                $this->statusTransitionPolicy->assertTransitionAllowed($rfq->status, $targetStatus);

                $this->rfqPersist->transitionStatus(
                    $rfq,
                    new TransitionRfqStatusCommand(
                        tenantId: $command->tenantId,
                        rfqId: $rfq->rfqId,
                        status: $targetStatus,
                    ),
                );
                ++$affected;
            }
        } else {
            foreach ($records as $rfq) {
                if ($rfq->tenantId !== $command->tenantId) {
                    throw RfqLifecyclePreconditionException::forRfq($rfq->rfqId, 'cross-tenant record provided.');
                }
                $this->statusTransitionPolicy->assertTransitionAllowed($rfq->status, $targetStatus);
            }
            $affected = $this->rfqPersist->applyBulkAction($command->tenantId, $bulkAction, $command->rfqIds);
        }

        return new RfqLifecycleOutcome(
            action: 'bulk_action',
            tenantId: $command->tenantId,
            status: $targetStatus,
            affectedCount: $affected,
        );
    }

    public function transitionStatus(TransitionRfqStatusCommand $command): RfqLifecycleOutcome
    {
        $rfq = $this->loadRfq($command->tenantId, $command->rfqId);
        $this->statusTransitionPolicy->assertTransitionAllowed($rfq->status, $command->status);

        $updated = $this->rfqPersist->transitionStatus($rfq, $command);

        return new RfqLifecycleOutcome(
            action: 'transition_status',
            tenantId: $command->tenantId,
            status: $updated->status,
            rfqId: $updated->rfqId,
            affectedCount: 1,
        );
    }

    public function remindInvitation(RemindRfqInvitationCommand $command): RfqLifecycleOutcome
    {
        $rfq = $this->loadRfq($command->tenantId, $command->rfqId);
        $invitation = $this->invitationQuery->findInvitationByTenantAndId(
            $command->tenantId,
            $command->rfqId,
            $command->invitationId,
        );

        if ($invitation === null) {
            throw RfqLifecyclePreconditionException::forRfq($command->rfqId, sprintf(
                'invitation "%s" was not found for tenant "%s".',
                $command->invitationId,
                $command->tenantId,
            ));
        }

        $marked = $this->invitationPersist->markInvitationReminded($invitation, $command);
        $this->invitationReminder->sendReminder($rfq, $marked, $command);

        return new RfqLifecycleOutcome(
            action: 'remind_invitation',
            tenantId: $command->tenantId,
            status: $marked->status,
            rfqId: $marked->rfqId,
            invitationId: $marked->id,
            affectedCount: 1,
        );
    }

    private function loadRfq(string $tenantId, string $rfqId): RfqLifecycleRecord
    {
        $rfq = $this->rfqQuery->findByTenantAndId($tenantId, $rfqId);

        if ($rfq === null) {
            throw RfqLifecyclePreconditionException::forRfq($rfqId, sprintf('RFQ "%s" could not be found for tenant "%s".', $rfqId, $tenantId));
        }

        return $rfq;
    }
}
