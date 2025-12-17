<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Batch Rejected Event
 * 
 * Fired when a payment batch is rejected during approval.
 */
final readonly class PaymentBatchRejectedEvent
{
    public function __construct(
        public string $batchId,
        public string $batchNumber,
        public string $tenantId,
        public Money $totalAmount,
        public int $itemCount,
        public string $rejectedBy,
        public string $rejectionReason,
        public int $rejectionLevel,
        public \DateTimeImmutable $rejectedAt,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create rejection event
     */
    public static function create(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        Money $totalAmount,
        int $itemCount,
        string $rejectedBy,
        string $reason,
        int $rejectionLevel = 1,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            itemCount: $itemCount,
            rejectedBy: $rejectedBy,
            rejectionReason: $reason,
            rejectionLevel: $rejectionLevel,
            rejectedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Create rejection due to policy violation
     */
    public static function policyViolation(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        Money $totalAmount,
        int $itemCount,
        string $rejectedBy,
        string $policyName,
    ): self {
        return self::create(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            itemCount: $itemCount,
            rejectedBy: $rejectedBy,
            reason: "Policy violation: {$policyName}",
        );
    }

    /**
     * Create rejection due to insufficient funds
     */
    public static function insufficientFunds(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        Money $totalAmount,
        int $itemCount,
        string $rejectedBy,
    ): self {
        return self::create(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            itemCount: $itemCount,
            rejectedBy: $rejectedBy,
            reason: 'Insufficient funds in bank account',
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.batch_rejected';
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
            'rejected_by' => $this->rejectedBy,
            'rejection_reason' => $this->rejectionReason,
            'rejection_level' => $this->rejectionLevel,
            'rejected_at' => $this->rejectedAt->format('c'),
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
