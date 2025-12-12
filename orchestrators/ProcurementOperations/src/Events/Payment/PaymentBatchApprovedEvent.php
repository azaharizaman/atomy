<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Batch Approved Event
 * 
 * Fired when a payment batch is approved.
 */
final readonly class PaymentBatchApprovedEvent
{
    public function __construct(
        public string $batchId,
        public string $batchNumber,
        public string $tenantId,
        public Money $totalAmount,
        public int $itemCount,
        public string $approvedBy,
        public \DateTimeImmutable $approvedAt,
        public bool $isFinalApproval,
        public int $approvalLevel,
        public ?string $approvalComments = null,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create final approval event
     */
    public static function finalApproval(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        Money $totalAmount,
        int $itemCount,
        string $approvedBy,
        int $approvalLevel = 1,
        ?string $comments = null,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            itemCount: $itemCount,
            approvedBy: $approvedBy,
            approvedAt: $now,
            isFinalApproval: true,
            approvalLevel: $approvalLevel,
            approvalComments: $comments,
            occurredAt: $now,
        );
    }

    /**
     * Create intermediate approval event (multi-level approval)
     */
    public static function intermediateApproval(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        Money $totalAmount,
        int $itemCount,
        string $approvedBy,
        int $approvalLevel,
        ?string $comments = null,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            itemCount: $itemCount,
            approvedBy: $approvedBy,
            approvedAt: $now,
            isFinalApproval: false,
            approvalLevel: $approvalLevel,
            approvalComments: $comments,
            occurredAt: $now,
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.batch_approved';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'batch_id' => $this->batchId,
            'batch_number' => $this->batchNumber,
            'tenant_id' => $this->tenantId,
            'total_amount' => $this->totalAmount->toArray(),
            'item_count' => $this->itemCount,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt->format('c'),
            'is_final_approval' => $this->isFinalApproval,
            'approval_level' => $this->approvalLevel,
            'approval_comments' => $this->approvalComments,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
