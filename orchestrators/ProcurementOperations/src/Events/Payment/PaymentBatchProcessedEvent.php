<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Batch Processed Event
 * 
 * Fired when a payment batch has been processed (bank file generated).
 */
final readonly class PaymentBatchProcessedEvent
{
    public function __construct(
        public string $batchId,
        public string $batchNumber,
        public string $tenantId,
        public string $paymentMethod,
        public Money $totalAmount,
        public int $totalItems,
        public int $successfulItems,
        public int $failedItems,
        public string $bankFileReference,
        public string $bankFileName,
        public string $fileFormat,
        public \DateTimeImmutable $processedAt,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create fully successful processing event
     */
    public static function fullySuccessful(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        string $paymentMethod,
        Money $totalAmount,
        int $totalItems,
        string $bankFileReference,
        string $bankFileName,
        string $fileFormat,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            totalAmount: $totalAmount,
            totalItems: $totalItems,
            successfulItems: $totalItems,
            failedItems: 0,
            bankFileReference: $bankFileReference,
            bankFileName: $bankFileName,
            fileFormat: $fileFormat,
            processedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Create partial success processing event
     */
    public static function partialSuccess(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        string $paymentMethod,
        Money $totalAmount,
        int $totalItems,
        int $successfulItems,
        int $failedItems,
        string $bankFileReference,
        string $bankFileName,
        string $fileFormat,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            totalAmount: $totalAmount,
            totalItems: $totalItems,
            successfulItems: $successfulItems,
            failedItems: $failedItems,
            bankFileReference: $bankFileReference,
            bankFileName: $bankFileName,
            fileFormat: $fileFormat,
            processedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Check if all payments were successful
     */
    public function isFullySuccessful(): bool
    {
        return $this->failedItems === 0;
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->totalItems === 0) {
            return 100.0;
        }

        return round(($this->successfulItems / $this->totalItems) * 100, 2);
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.batch_processed';
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
            'total_amount' => $this->totalAmount->toArray(),
            'total_items' => $this->totalItems,
            'successful_items' => $this->successfulItems,
            'failed_items' => $this->failedItems,
            'success_rate' => $this->getSuccessRate(),
            'bank_file_reference' => $this->bankFileReference,
            'bank_file_name' => $this->bankFileName,
            'file_format' => $this->fileFormat,
            'is_fully_successful' => $this->isFullySuccessful(),
            'processed_at' => $this->processedAt->format('c'),
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
