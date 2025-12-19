<?php

declare(strict_types=1);

namespace Nexus\Payment\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\DisbursementInterface;
use Nexus\Payment\Contracts\DisbursementManagerInterface;
use Nexus\Payment\Contracts\DisbursementPersistInterface;
use Nexus\Payment\Contracts\DisbursementQueryInterface;
use Nexus\Payment\Contracts\PaymentExecutorInterface;
use Nexus\Payment\Entities\Disbursement;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Events\DisbursementApprovedEvent;
use Nexus\Payment\Events\DisbursementCancelledEvent;
use Nexus\Payment\Events\DisbursementCompletedEvent;
use Nexus\Payment\Events\DisbursementCreatedEvent;
use Nexus\Payment\Events\DisbursementFailedEvent;
use Nexus\Payment\Events\DisbursementRejectedEvent;
use Nexus\Payment\Exceptions\DisbursementNotFoundException;
use Nexus\Payment\Exceptions\InvalidDisbursementStatusException;
use Nexus\Payment\Exceptions\PaymentValidationException;
use Nexus\Payment\ValueObjects\RecipientInfo;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Disbursement Manager - Orchestrates disbursement lifecycle.
 *
 * This is the primary entry point for creating and managing disbursements
 * (outbound payments to vendors/recipients).
 */
final class DisbursementManager implements DisbursementManagerInterface
{
    public function __construct(
        private readonly DisbursementQueryInterface $disbursementQuery,
        private readonly DisbursementPersistInterface $disbursementPersist,
        private readonly PaymentValidator $validator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly ?PaymentExecutorInterface $defaultExecutor = null,
    ) {}

    /**
     * Create a new disbursement.
     *
     * @param array<string>|null $sourceDocumentIds
     * @param array<string, mixed>|null $metadata
     */
    public function create(
        string $tenantId,
        Money $amount,
        RecipientInfo $recipient,
        PaymentMethodType $methodType,
        string $createdBy,
        ?array $sourceDocumentIds = null,
        ?\DateTimeImmutable $scheduledDate = null,
        ?array $metadata = null,
    ): DisbursementInterface {
        // Validate amount
        $this->validator->validateAmount($amount);

        // Generate unique reference number
        $referenceNumber = $this->generateReferenceNumber($tenantId);

        // Build options array
        $options = [];
        if ($sourceDocumentIds !== null) {
            $options['source_document_ids'] = $sourceDocumentIds;
        }
        if ($scheduledDate !== null) {
            $options['scheduled_date'] = $scheduledDate;
        }
        if ($metadata !== null) {
            $options['metadata'] = $metadata;
        }

        // Create disbursement entity
        $disbursement = Disbursement::create(
            id: $this->generateId(),
            tenantId: $tenantId,
            referenceNumber: $referenceNumber,
            amount: $amount,
            recipient: $recipient,
            methodType: $methodType,
            createdBy: $createdBy,
            options: $options,
        );

        // Persist
        $this->disbursementPersist->save($disbursement);

        // Dispatch event
        $this->eventDispatcher->dispatch(new DisbursementCreatedEvent(
            disbursementId: $disbursement->getId(),
            tenantId: $tenantId,
            referenceNumber: $referenceNumber,
            amount: $amount,
            recipient: $recipient,
            createdBy: $createdBy,
            sourceDocumentIds: $sourceDocumentIds ?? [],
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->logger->info('Disbursement created', [
            'disbursement_id' => $disbursement->getId(),
            'reference_number' => $referenceNumber,
            'amount' => $amount->format(),
            'recipient' => $recipient->name,
        ]);

        return $disbursement;
    }

    /**
     * Submit disbursement for approval.
     */
    public function submitForApproval(string $disbursementId): DisbursementInterface
    {
        $disbursement = $this->findOrFail($disbursementId);

        if ($disbursement->getStatus() !== DisbursementStatus::DRAFT) {
            throw new InvalidDisbursementStatusException(
                $disbursement->getStatus(),
                DisbursementStatus::DRAFT,
                'Only draft disbursements can be submitted for approval'
            );
        }

        $disbursement->submitForApproval();
        $this->disbursementPersist->save($disbursement);

        $this->logger->info('Disbursement submitted for approval', [
            'disbursement_id' => $disbursement->getId(),
        ]);

        return $disbursement;
    }

    /**
     * Approve a disbursement.
     */
    public function approve(
        string $disbursementId,
        string $approvedBy,
        ?string $notes = null,
    ): DisbursementInterface {
        $disbursement = $this->findOrFail($disbursementId);

        if ($disbursement->getStatus() !== DisbursementStatus::PENDING_APPROVAL) {
            throw new InvalidDisbursementStatusException(
                $disbursement->getStatus(),
                DisbursementStatus::PENDING_APPROVAL,
                'Only pending approval disbursements can be approved'
            );
        }

        $disbursement->approve($approvedBy, $notes);
        $this->disbursementPersist->save($disbursement);

        $this->eventDispatcher->dispatch(new DisbursementApprovedEvent(
            disbursementId: $disbursement->getId(),
            tenantId: $disbursement->getTenantId(),
            amount: $disbursement->getAmount(),
            approvedBy: $approvedBy,
            notes: $notes,
            approvedAt: $disbursement->getApprovedAt() ?? new \DateTimeImmutable(),
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->logger->info('Disbursement approved', [
            'disbursement_id' => $disbursement->getId(),
            'approved_by' => $approvedBy,
        ]);

        return $disbursement;
    }

    /**
     * Reject a disbursement.
     */
    public function reject(
        string $disbursementId,
        string $rejectedBy,
        string $reason,
    ): DisbursementInterface {
        $disbursement = $this->findOrFail($disbursementId);

        if ($disbursement->getStatus() !== DisbursementStatus::PENDING_APPROVAL) {
            throw new InvalidDisbursementStatusException(
                $disbursement->getStatus(),
                DisbursementStatus::PENDING_APPROVAL,
                'Only pending approval disbursements can be rejected'
            );
        }

        $disbursement->reject($rejectedBy, $reason);
        $this->disbursementPersist->save($disbursement);

        $this->eventDispatcher->dispatch(new DisbursementRejectedEvent(
            disbursementId: $disbursement->getId(),
            tenantId: $disbursement->getTenantId(),
            amount: $disbursement->getAmount(),
            rejectedBy: $rejectedBy,
            reason: $reason,
            rejectedAt: new \DateTimeImmutable(),
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->logger->info('Disbursement rejected', [
            'disbursement_id' => $disbursement->getId(),
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
        ]);

        return $disbursement;
    }

    /**
     * Process an approved disbursement.
     */
    public function process(
        string $disbursementId,
        ?PaymentExecutorInterface $executor = null,
    ): DisbursementInterface {
        $disbursement = $this->findOrFail($disbursementId);
        $executor ??= $this->defaultExecutor;

        if ($disbursement->getStatus() !== DisbursementStatus::APPROVED) {
            throw new InvalidDisbursementStatusException(
                $disbursement->getStatus(),
                DisbursementStatus::APPROVED,
                'Only approved disbursements can be processed'
            );
        }

        // Check if scheduled for future
        if ($disbursement->getScheduledDate() !== null) {
            $now = new \DateTimeImmutable();
            if ($disbursement->getScheduledDate() > $now) {
                throw new PaymentValidationException(
                    "Disbursement is scheduled for {$disbursement->getScheduledDate()->format('Y-m-d')} and cannot be processed yet"
                );
            }
        }

        // Mark as processing
        $disbursement->markAsProcessing();
        $this->disbursementPersist->save($disbursement);

        try {
            // Here you would integrate with the actual payment execution
            // For now, we'll just mark it as completed
            if ($executor !== null) {
                // In real implementation, create a PaymentTransaction and execute it
                // $paymentResult = $executor->execute($paymentTransaction);
            }

            // For demonstration, we'll mark as completed directly
            $paymentTransactionId = 'ptxn_' . bin2hex(random_bytes(16));
            $disbursement->markAsCompleted($paymentTransactionId);
            $this->disbursementPersist->save($disbursement);
            $this->disbursementPersist->linkPaymentTransaction(
                $disbursement->getId(),
                $paymentTransactionId
            );

            $this->eventDispatcher->dispatch(new DisbursementCompletedEvent(
                disbursementId: $disbursement->getId(),
                tenantId: $disbursement->getTenantId(),
                amount: $disbursement->getAmount(),
                paymentTransactionId: $paymentTransactionId,
                completedAt: new \DateTimeImmutable(),
                occurredAt: new \DateTimeImmutable(),
            ));

            $this->logger->info('Disbursement processed', [
                'disbursement_id' => $disbursement->getId(),
                'payment_transaction_id' => $paymentTransactionId,
            ]);

            return $disbursement;
        } catch (\Throwable $e) {
            $disbursement->markAsFailed('PROCESSING_ERROR', $e->getMessage());
            $this->disbursementPersist->save($disbursement);

            $this->eventDispatcher->dispatch(new DisbursementFailedEvent(
                disbursementId: $disbursement->getId(),
                tenantId: $disbursement->getTenantId(),
                amount: $disbursement->getAmount(),
                failureCode: 'PROCESSING_ERROR',
                failureMessage: $e->getMessage(),
                isRetryable: true,
                occurredAt: new \DateTimeImmutable(),
            ));

            $this->logger->error('Disbursement processing failed', [
                'disbursement_id' => $disbursement->getId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel a disbursement.
     */
    public function cancel(
        string $disbursementId,
        string $cancelledBy,
        string $reason,
    ): DisbursementInterface {
        $disbursement = $this->findOrFail($disbursementId);

        // Can only cancel drafts, pending approval, or approved disbursements
        $cancellableStatuses = [
            DisbursementStatus::DRAFT,
            DisbursementStatus::PENDING_APPROVAL,
            DisbursementStatus::APPROVED,
        ];

        if (!in_array($disbursement->getStatus(), $cancellableStatuses, true)) {
            throw new InvalidDisbursementStatusException(
                $disbursement->getStatus(),
                DisbursementStatus::CANCELLED,
                "Disbursement in status {$disbursement->getStatus()->value} cannot be cancelled"
            );
        }

        $disbursement->cancel($reason);
        $this->disbursementPersist->save($disbursement);

        $this->eventDispatcher->dispatch(new DisbursementCancelledEvent(
            disbursementId: $disbursement->getId(),
            tenantId: $disbursement->getTenantId(),
            amount: $disbursement->getAmount(),
            cancelledBy: $cancelledBy,
            reason: $reason,
            cancelledAt: new \DateTimeImmutable(),
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->logger->info('Disbursement cancelled', [
            'disbursement_id' => $disbursement->getId(),
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
        ]);

        return $disbursement;
    }

    /**
     * Schedule a disbursement for future processing.
     */
    public function schedule(
        string $disbursementId,
        \DateTimeImmutable $scheduledDate,
    ): DisbursementInterface {
        $disbursement = $this->findOrFail($disbursementId);

        if ($disbursement->getStatus() === DisbursementStatus::COMPLETED ||
            $disbursement->getStatus() === DisbursementStatus::CANCELLED ||
            $disbursement->getStatus() === DisbursementStatus::FAILED) {
            throw new InvalidDisbursementStatusException(
                $disbursement->getStatus(),
                DisbursementStatus::APPROVED,
                "Cannot schedule a {$disbursement->getStatus()->value} disbursement"
            );
        }

        $now = new \DateTimeImmutable();
        if ($scheduledDate <= $now) {
            throw new PaymentValidationException(
                'Scheduled date must be in the future'
            );
        }

        $disbursement->schedule($scheduledDate);
        $this->disbursementPersist->save($disbursement);

        $this->logger->info('Disbursement scheduled', [
            'disbursement_id' => $disbursement->getId(),
            'scheduled_date' => $scheduledDate->format('Y-m-d H:i:s'),
        ]);

        return $disbursement;
    }

    /**
     * Get disbursements pending approval.
     *
     * @return array<DisbursementInterface>
     */
    public function getPendingApprovals(string $tenantId): array
    {
        // Repository is responsible for tenant scoping via TenantContextInterface.
        // The $tenantId parameter is kept for interface symmetry with other methods
        // and for potential future use, but is not used for additional filtering here.
        $pending = $this->disbursementQuery->findPendingApproval();

        return $pending;
    }

    /**
     * Get disbursements ready for processing (approved and not scheduled or past scheduled date).
     *
     * @return array<DisbursementInterface>
     */
    public function getReadyForProcessing(string $tenantId): array
    {
        $approved = $this->disbursementQuery->findByStatus(DisbursementStatus::APPROVED);

        $now = new \DateTimeImmutable();

        // Filter by tenant and scheduled date
        return array_filter($approved, function (DisbursementInterface $d) use ($now, $tenantId) {
            // Must belong to tenant
            if ($d->getTenantId() !== $tenantId) {
                return false;
            }

            $scheduledDate = $d->getScheduledDate();

            return $scheduledDate === null || $scheduledDate <= $now;
        });
    }

    /**
     * Get a disbursement by ID.
     */
    public function getById(string $id): DisbursementInterface
    {
        return $this->findOrFail($id);
    }

    /**
     * Find a disbursement by ID or throw.
     */
    public function findOrFail(string $disbursementId): DisbursementInterface
    {
        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        return $disbursement;
    }

    /**
     * Link source documents to a disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param array<string> $documentIds Document IDs to link
     * @return DisbursementInterface Updated disbursement
     */
    public function linkSourceDocuments(
        string $disbursementId,
        array $documentIds,
    ): DisbursementInterface {
        $disbursement = $this->findOrFail($disbursementId);

        $disbursement->linkSourceDocuments($documentIds);
        $this->disbursementPersist->save($disbursement);

        $this->logger->info('Source documents linked to disbursement', [
            'disbursement_id' => $disbursementId,
            'document_ids' => $documentIds,
        ]);

        return $disbursement;
    }

    /**
     * Get disbursements by status.
     *
     * @return array<DisbursementInterface>
     */
    public function findByStatus(DisbursementStatus $status): array
    {
        return $this->disbursementQuery->findByStatus($status);
    }

    /**
     * Get disbursement status.
     */
    public function getStatus(string $disbursementId): DisbursementStatus
    {
        return $this->findOrFail($disbursementId)->getStatus();
    }

    /**
     * Generate a unique disbursement ID.
     */
    private function generateId(): string
    {
        return 'disb_' . bin2hex(random_bytes(16));
    }

    /**
     * Generate a unique reference number.
     */
    private function generateReferenceNumber(string $tenantId): string
    {
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(4)));

        return "DISB-{$date}-{$random}";
    }
}
