<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\WireType;

/**
 * Result DTO for wire transfer operations.
 */
final readonly class WireTransferResult
{
    /**
     * @param string $transferId Unique transfer identifier
     * @param bool $success Whether the transfer was successful
     * @param string $status Transfer status
     * @param Money $amount Transfer amount
     * @param WireType $wireType Type of wire transfer
     * @param string|null $confirmationNumber Bank confirmation number
     * @param string|null $federalReferenceNumber Federal reference number (Fedwire)
     * @param string|null $swiftReferenceNumber SWIFT reference number (SWIFT)
     * @param string|null $imadNumber Input Message Accountability Data
     * @param string|null $omadNumber Output Message Accountability Data
     * @param array<string> $errors Any errors encountered
     * @param Money|null $fee Wire transfer fee charged
     * @param \DateTimeImmutable $initiatedAt When the transfer was initiated
     * @param \DateTimeImmutable|null $completedAt When the transfer completed
     * @param \DateTimeImmutable|null $expectedSettlementDate Expected settlement date
     */
    public function __construct(
        public string $transferId,
        public bool $success,
        public string $status,
        public Money $amount,
        public WireType $wireType,
        public ?string $confirmationNumber = null,
        public ?string $federalReferenceNumber = null,
        public ?string $swiftReferenceNumber = null,
        public ?string $imadNumber = null,
        public ?string $omadNumber = null,
        public array $errors = [],
        public ?Money $fee = null,
        public \DateTimeImmutable $initiatedAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $completedAt = null,
        public ?\DateTimeImmutable $expectedSettlementDate = null,
    ) {}

    /**
     * Create a successful wire transfer result.
     */
    public static function success(
        string $transferId,
        Money $amount,
        WireType $wireType,
        string $confirmationNumber,
        ?string $federalReferenceNumber = null,
        ?Money $fee = null,
        ?\DateTimeImmutable $expectedSettlementDate = null,
    ): self {
        return new self(
            transferId: $transferId,
            success: true,
            status: 'completed',
            amount: $amount,
            wireType: $wireType,
            confirmationNumber: $confirmationNumber,
            federalReferenceNumber: $federalReferenceNumber,
            fee: $fee,
            expectedSettlementDate: $expectedSettlementDate,
        );
    }

    /**
     * Create a pending wire transfer result.
     */
    public static function pending(
        string $transferId,
        Money $amount,
        WireType $wireType,
        ?string $confirmationNumber = null,
        ?\DateTimeImmutable $expectedSettlementDate = null,
    ): self {
        return new self(
            transferId: $transferId,
            success: true,
            status: 'pending',
            amount: $amount,
            wireType: $wireType,
            confirmationNumber: $confirmationNumber,
            expectedSettlementDate: $expectedSettlementDate,
        );
    }

    /**
     * Create a failed wire transfer result.
     *
     * @param array<string> $errors
     */
    public static function failure(
        string $transferId,
        Money $amount,
        WireType $wireType,
        array $errors,
    ): self {
        return new self(
            transferId: $transferId,
            success: false,
            status: 'failed',
            amount: $amount,
            wireType: $wireType,
            errors: $errors,
        );
    }

    /**
     * Get the primary reference number.
     */
    public function getReferenceNumber(): ?string
    {
        return $this->federalReferenceNumber
            ?? $this->swiftReferenceNumber
            ?? $this->confirmationNumber;
    }

    /**
     * Check if the wire is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the wire is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get the total cost including fees.
     */
    public function getTotalCost(): Money
    {
        if ($this->fee === null) {
            return $this->amount;
        }

        return $this->amount->add($this->fee);
    }
}
