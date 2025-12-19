<?php

declare(strict_types=1);

namespace Nexus\Payment\Entities;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\DisbursementInterface;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\ValueObjects\RecipientInfo;

/**
 * Disbursement entity.
 *
 * Represents an outgoing payment to vendors/parties.
 */
final class Disbursement implements DisbursementInterface
{
    private DisbursementStatus $status;

    private ?string $sourceAccountId = null;

    private ?string $description = null;

    private ?string $approvalNotes = null;

    private ?string $approvedBy = null;

    private ?\DateTimeImmutable $approvedAt = null;

    private ?string $rejectedBy = null;

    private ?string $rejectionReason = null;

    private ?\DateTimeImmutable $rejectedAt = null;

    private ?string $paymentTransactionId = null;

    private ?\DateTimeImmutable $scheduledDate = null;

    private ?\DateTimeImmutable $processedAt = null;

    private ?\DateTimeImmutable $completedAt = null;

    /** @var array<string> */
    private array $sourceDocumentIds;

    /** @var array<string, mixed> */
    private array $metadata = [];

    /**
     * @param array<string> $sourceDocumentIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $referenceNumber,
        private readonly Money $amount,
        private readonly RecipientInfo $recipient,
        private readonly PaymentMethodType $methodType,
        private readonly string $createdBy,
        private readonly bool $requiresApproval,
        private readonly \DateTimeImmutable $createdAt,
        array $sourceDocumentIds = [],
    ) {
        $this->status = $requiresApproval
            ? DisbursementStatus::DRAFT
            : DisbursementStatus::APPROVED;
        $this->sourceDocumentIds = $sourceDocumentIds;
    }

    /**
     * Create a new disbursement.
     *
     * @param array<string, mixed> $options
     */
    public static function create(
        string $id,
        string $tenantId,
        string $referenceNumber,
        Money $amount,
        RecipientInfo $recipient,
        PaymentMethodType $methodType,
        string $createdBy,
        array $options = [],
    ): self {
        $disbursement = new self(
            id: $id,
            tenantId: $tenantId,
            referenceNumber: $referenceNumber,
            amount: $amount,
            recipient: $recipient,
            methodType: $methodType,
            createdBy: $createdBy,
            requiresApproval: $options['requires_approval'] ?? true,
            createdAt: new \DateTimeImmutable(),
            sourceDocumentIds: $options['source_document_ids'] ?? [],
        );

        if (isset($options['scheduled_date'])) {
            $disbursement->scheduledDate = $options['scheduled_date'];
        }

        if (isset($options['metadata'])) {
            $disbursement->metadata = $options['metadata'];
        }

        if (isset($options['source_account_id'])) {
            $disbursement->sourceAccountId = $options['source_account_id'];
        }

        if (isset($options['description'])) {
            $disbursement->description = $options['description'];
        }

        return $disbursement;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function getStatus(): DisbursementStatus
    {
        return $this->status;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getRecipient(): RecipientInfo
    {
        return $this->recipient;
    }

    public function getMethodType(): PaymentMethodType
    {
        return $this->methodType;
    }

    public function getSourceAccountId(): ?string
    {
        return $this->sourceAccountId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string>
     */
    public function getSourceDocumentIds(): array
    {
        return $this->sourceDocumentIds;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getApprovalNotes(): ?string
    {
        return $this->approvalNotes;
    }

    public function getRejectedBy(): ?string
    {
        return $this->rejectedBy;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function getPaymentTransactionId(): ?string
    {
        return $this->paymentTransactionId;
    }

    public function getScheduledDate(): ?\DateTimeImmutable
    {
        return $this->scheduledDate;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function canTransitionTo(DisbursementStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function isReadyForProcessing(): bool
    {
        if ($this->status !== DisbursementStatus::APPROVED) {
            return false;
        }

        if ($this->scheduledDate !== null && $this->scheduledDate > new \DateTimeImmutable()) {
            return false;
        }

        return true;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            DisbursementStatus::COMPLETED,
            DisbursementStatus::CANCELLED,
            DisbursementStatus::REJECTED,
        ], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === DisbursementStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === DisbursementStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === DisbursementStatus::CANCELLED;
    }

    /**
     * Submit for approval.
     */
    public function submitForApproval(): void
    {
        $this->transitionTo(DisbursementStatus::PENDING_APPROVAL);
    }

    /**
     * Approve the disbursement.
     */
    public function approve(string $approvedBy, ?string $notes = null): void
    {
        $this->transitionTo(DisbursementStatus::APPROVED);
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new \DateTimeImmutable();
        $this->approvalNotes = $notes;

        if ($notes !== null) {
            $this->metadata['approval_notes'] = $notes;
        }
    }

    /**
     * Reject the disbursement.
     */
    public function reject(string $rejectedBy, string $reason): void
    {
        $this->transitionTo(DisbursementStatus::REJECTED);
        $this->rejectedBy = $rejectedBy;
        $this->rejectionReason = $reason;
        $this->rejectedAt = new \DateTimeImmutable();
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): void
    {
        $this->transitionTo(DisbursementStatus::PROCESSING);
        $this->processedAt = new \DateTimeImmutable();
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(string $paymentTransactionId): void
    {
        $this->transitionTo(DisbursementStatus::COMPLETED);
        $this->paymentTransactionId = $paymentTransactionId;
        $this->completedAt = new \DateTimeImmutable();
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $failureCode, string $failureMessage): void
    {
        $this->transitionTo(DisbursementStatus::FAILED);
        $this->metadata['failure_code'] = $failureCode;
        $this->metadata['failure_message'] = $failureMessage;
    }

    /**
     * Cancel the disbursement.
     */
    public function cancel(?string $reason = null): void
    {
        $this->transitionTo(DisbursementStatus::CANCELLED);

        if ($reason !== null) {
            $this->metadata['cancellation_reason'] = $reason;
        }
    }

    /**
     * Link source documents.
     *
     * @param array<string> $documentIds
     */
    public function linkSourceDocuments(array $documentIds): void
    {
        foreach ($documentIds as $documentId) {
            if (!in_array($documentId, $this->sourceDocumentIds, true)) {
                $this->sourceDocumentIds[] = $documentId;
            }
        }
    }

    /**
     * Schedule for future processing.
     */
    public function schedule(\DateTimeImmutable $date): void
    {
        $this->scheduledDate = $date;
    }

    /**
     * Add metadata.
     *
     * @param array<string, mixed> $metadata
     */
    public function addMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * Transition to a new status.
     */
    private function transitionTo(DisbursementStatus $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus;
    }
}
