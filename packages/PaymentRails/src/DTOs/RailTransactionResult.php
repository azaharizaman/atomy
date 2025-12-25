<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Generic result DTO for rail transactions.
 *
 * This provides a unified result format across all payment rails.
 */
final readonly class RailTransactionResult
{
    /**
     * @param string $transactionId Unique transaction identifier
     * @param bool $success Whether the transaction was successful
     * @param string $status Transaction status
     * @param RailType $railType The payment rail used
     * @param Money $amount Transaction amount
     * @param string|null $referenceNumber Primary reference number
     * @param array<string> $errors Any errors encountered
     * @param array<string, mixed> $metadata Additional transaction metadata
     * @param Money|null $fees Any fees charged
     * @param \DateTimeImmutable $processedAt Processing timestamp
     * @param \DateTimeImmutable|null $settledAt Settlement timestamp
     * @param \DateTimeImmutable|null $expectedSettlementDate Expected settlement
     */
    public function __construct(
        public string $transactionId,
        public bool $success,
        public string $status,
        public RailType $railType,
        public Money $amount,
        public ?string $referenceNumber = null,
        public array $errors = [],
        public array $metadata = [],
        public ?Money $fees = null,
        public \DateTimeImmutable $processedAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $settledAt = null,
        public ?\DateTimeImmutable $expectedSettlementDate = null,
    ) {}

    /**
     * Create a successful transaction result.
     */
    public static function success(
        string $transactionId,
        RailType $railType,
        Money $amount,
        string $referenceNumber,
        ?Money $fees = null,
        ?\DateTimeImmutable $expectedSettlementDate = null,
        array $metadata = [],
    ): self {
        return new self(
            transactionId: $transactionId,
            success: true,
            status: 'completed',
            railType: $railType,
            amount: $amount,
            referenceNumber: $referenceNumber,
            metadata: $metadata,
            fees: $fees,
            expectedSettlementDate: $expectedSettlementDate,
        );
    }

    /**
     * Create a pending transaction result.
     */
    public static function pending(
        string $transactionId,
        RailType $railType,
        Money $amount,
        ?string $referenceNumber = null,
        ?\DateTimeImmutable $expectedSettlementDate = null,
        array $metadata = [],
    ): self {
        return new self(
            transactionId: $transactionId,
            success: true,
            status: 'pending',
            railType: $railType,
            amount: $amount,
            referenceNumber: $referenceNumber,
            metadata: $metadata,
            expectedSettlementDate: $expectedSettlementDate,
        );
    }

    /**
     * Create a failed transaction result.
     *
     * @param array<string> $errors
     */
    public static function failure(
        string $transactionId,
        RailType $railType,
        Money $amount,
        array $errors,
    ): self {
        return new self(
            transactionId: $transactionId,
            success: false,
            status: 'failed',
            railType: $railType,
            amount: $amount,
            errors: $errors,
        );
    }

    /**
     * Create from an ACH batch result.
     */
    public static function fromAchResult(AchBatchResult $result): self
    {
        return new self(
            transactionId: $result->fileId,
            success: $result->success,
            status: $result->status->value,
            railType: RailType::ACH,
            amount: $result->getTotalAmount(),
            referenceNumber: $result->batchId,
            errors: $result->errors,
            metadata: ['entry_count' => $result->entryCount],
            expectedSettlementDate: $result->effectiveDate,
        );
    }

    /**
     * Create from a wire transfer result.
     */
    public static function fromWireResult(WireTransferResult $result): self
    {
        return new self(
            transactionId: $result->transferId,
            success: $result->success,
            status: $result->status,
            railType: RailType::WIRE,
            amount: $result->amount,
            referenceNumber: $result->getReferenceNumber(),
            errors: $result->errors,
            fees: $result->fee,
            expectedSettlementDate: $result->expectedSettlementDate,
        );
    }

    /**
     * Create from a check result.
     */
    public static function fromCheckResult(CheckResult $result): self
    {
        return new self(
            transactionId: $result->checkId,
            success: $result->success,
            status: $result->status->value,
            railType: RailType::CHECK,
            amount: $result->amount,
            referenceNumber: $result->checkNumber?->toString(),
            errors: $result->errors,
            metadata: ['payee' => $result->payeeName],
        );
    }

    /**
     * Create from a virtual card result.
     */
    public static function fromVirtualCardResult(VirtualCardResult $result): self
    {
        return new self(
            transactionId: $result->cardId,
            success: $result->success,
            status: $result->status->value,
            railType: RailType::VIRTUAL_CARD,
            amount: $result->creditLimit,
            referenceNumber: $result->maskedCardNumber,
            errors: $result->errors,
            metadata: [
                'card_type' => $result->cardType->value,
                'available_credit' => $result->availableCredit->getAmount(),
            ],
            expectedSettlementDate: $result->expiresAt,
        );
    }

    /**
     * Check if the transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transaction failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed' || !$this->success;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get the total amount including fees.
     */
    public function getTotalAmount(): Money
    {
        if ($this->fees === null) {
            return $this->amount;
        }

        return $this->amount->add($this->fees);
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
