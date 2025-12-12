<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Batch Submitted Event
 * 
 * Fired when a payment batch is submitted for approval.
 */
final readonly class PaymentBatchSubmittedEvent
{
    public function __construct(
        public string $batchId,
        public string $batchNumber,
        public string $tenantId,
        public Money $totalAmount,
        public int $itemCount,
        public int $vendorCount,
        public string $submittedBy,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create event from batch submission
     */
    public static function create(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        Money $totalAmount,
        int $itemCount,
        int $vendorCount,
        string $submittedBy,
    ): self {
        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            itemCount: $itemCount,
            vendorCount: $vendorCount,
            submittedBy: $submittedBy,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.batch_submitted';
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
            'vendor_count' => $this->vendorCount,
            'submitted_by' => $this->submittedBy,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
