<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Item Failed Event
 * 
 * Fired when an individual payment item fails processing.
 */
final readonly class PaymentItemFailedEvent
{
    public function __construct(
        public string $paymentItemId,
        public string $batchId,
        public string $vendorId,
        public string $vendorName,
        public Money $amount,
        public string $failureReason,
        public string $failureCode,
        public bool $isRetryable,
        public ?string $bankErrorCode = null,
        public ?string $bankErrorMessage = null,
        public \DateTimeImmutable $failedAt,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create failure due to invalid bank account
     */
    public static function invalidBankAccount(
        string $paymentItemId,
        string $batchId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        ?string $bankErrorCode = null,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            paymentItemId: $paymentItemId,
            batchId: $batchId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            failureReason: 'Invalid or closed bank account',
            failureCode: 'INVALID_BANK_ACCOUNT',
            isRetryable: false,
            bankErrorCode: $bankErrorCode,
            failedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Create failure due to insufficient funds
     */
    public static function insufficientFunds(
        string $paymentItemId,
        string $batchId,
        string $vendorId,
        string $vendorName,
        Money $amount,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            paymentItemId: $paymentItemId,
            batchId: $batchId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            failureReason: 'Insufficient funds in source account',
            failureCode: 'INSUFFICIENT_FUNDS',
            isRetryable: true,
            failedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Create failure due to bank rejection
     */
    public static function bankRejection(
        string $paymentItemId,
        string $batchId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        string $bankErrorCode,
        string $bankErrorMessage,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            paymentItemId: $paymentItemId,
            batchId: $batchId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            failureReason: 'Payment rejected by bank',
            failureCode: 'BANK_REJECTION',
            isRetryable: false,
            bankErrorCode: $bankErrorCode,
            bankErrorMessage: $bankErrorMessage,
            failedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Create failure due to network/system error (retryable)
     */
    public static function systemError(
        string $paymentItemId,
        string $batchId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        string $errorMessage,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            paymentItemId: $paymentItemId,
            batchId: $batchId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            amount: $amount,
            failureReason: $errorMessage,
            failureCode: 'SYSTEM_ERROR',
            isRetryable: true,
            failedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.item_failed';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'payment_item_id' => $this->paymentItemId,
            'batch_id' => $this->batchId,
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'amount' => $this->amount->toArray(),
            'failure_reason' => $this->failureReason,
            'failure_code' => $this->failureCode,
            'is_retryable' => $this->isRetryable,
            'bank_error_code' => $this->bankErrorCode,
            'bank_error_message' => $this->bankErrorMessage,
            'failed_at' => $this->failedAt->format('c'),
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
