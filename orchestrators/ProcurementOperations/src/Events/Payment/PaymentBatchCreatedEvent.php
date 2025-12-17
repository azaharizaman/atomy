<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Batch Created Event
 * 
 * Fired when a new payment batch is created.
 */
final readonly class PaymentBatchCreatedEvent
{
    public function __construct(
        public string $batchId,
        public string $batchNumber,
        public string $tenantId,
        public string $paymentMethod,
        public string $bankAccountId,
        public \DateTimeImmutable $paymentDate,
        public string $currency,
        public string $createdBy,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create event from batch creation
     */
    public static function create(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        string $paymentMethod,
        string $bankAccountId,
        \DateTimeImmutable $paymentDate,
        string $currency,
        string $createdBy,
    ): self {
        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            paymentDate: $paymentDate,
            currency: $currency,
            createdBy: $createdBy,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.batch_created';
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
            'payment_method' => $this->paymentMethod,
            'bank_account_id' => $this->bankAccountId,
            'payment_date' => $this->paymentDate->format('Y-m-d'),
            'currency' => $this->currency,
            'created_by' => $this->createdBy,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
