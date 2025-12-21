<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Enums\TransactionCode;

/**
 * Represents an individual ACH entry (transaction) within a batch.
 *
 * An ACH entry contains all the information needed to transfer funds
 * to or from a bank account via the ACH network.
 */
final class AchEntry
{
    /**
     * @param string $id Unique identifier for the entry
     * @param TransactionCode $transactionCode The ACH transaction code
     * @param RoutingNumber $routingNumber Receiving bank routing number
     * @param string $accountNumber Receiving account number
     * @param AccountType $accountType Account type (checking/savings)
     * @param Money $amount Transaction amount
     * @param string $individualName Name of the receiver
     * @param string $individualId Receiver identification (SSN, EIN, etc.)
     * @param string|null $discretionaryData Optional discretionary data (2 chars)
     * @param string|null $addenda Optional addenda information
     * @param string|null $traceNumber Trace number for tracking
     */
    public function __construct(
        public readonly string $id,
        public readonly TransactionCode $transactionCode,
        public readonly RoutingNumber $routingNumber,
        public readonly string $accountNumber,
        public readonly AccountType $accountType,
        public readonly Money $amount,
        public readonly string $individualName,
        public readonly string $individualId,
        public readonly ?string $discretionaryData = null,
        public readonly ?string $addenda = null,
        public readonly ?string $traceNumber = null,
    ) {}

    /**
     * Create a credit (deposit) entry.
     */
    public static function credit(
        string $id,
        RoutingNumber $routingNumber,
        string $accountNumber,
        AccountType $accountType,
        Money $amount,
        string $individualName,
        string $individualId,
        ?string $addenda = null,
    ): self {
        $transactionCode = $accountType === AccountType::SAVINGS
            ? TransactionCode::SAVINGS_CREDIT
            : TransactionCode::CHECKING_CREDIT;

        return new self(
            id: $id,
            transactionCode: $transactionCode,
            routingNumber: $routingNumber,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: $amount,
            individualName: $individualName,
            individualId: $individualId,
            addenda: $addenda,
        );
    }

    /**
     * Create a debit (withdrawal) entry.
     */
    public static function debit(
        string $id,
        RoutingNumber $routingNumber,
        string $accountNumber,
        AccountType $accountType,
        Money $amount,
        string $individualName,
        string $individualId,
        ?string $addenda = null,
    ): self {
        $transactionCode = $accountType === AccountType::SAVINGS
            ? TransactionCode::SAVINGS_DEBIT
            : TransactionCode::CHECKING_DEBIT;

        return new self(
            id: $id,
            transactionCode: $transactionCode,
            routingNumber: $routingNumber,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: $amount,
            individualName: $individualName,
            individualId: $individualId,
            addenda: $addenda,
        );
    }

    /**
     * Create a prenote (zero-dollar test) entry.
     */
    public static function prenote(
        string $id,
        RoutingNumber $routingNumber,
        string $accountNumber,
        AccountType $accountType,
        string $individualName,
        string $individualId,
        bool $isCredit = true,
    ): self {
        $transactionCode = match (true) {
            $accountType === AccountType::SAVINGS && $isCredit => TransactionCode::SAVINGS_CREDIT_PRENOTE,
            $accountType === AccountType::SAVINGS && !$isCredit => TransactionCode::SAVINGS_DEBIT_PRENOTE,
            $accountType === AccountType::CHECKING && $isCredit => TransactionCode::CHECKING_CREDIT_PRENOTE,
            default => TransactionCode::CHECKING_DEBIT_PRENOTE,
        };

        return new self(
            id: $id,
            transactionCode: $transactionCode,
            routingNumber: $routingNumber,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: Money::zero('USD'),
            individualName: $individualName,
            individualId: $individualId,
        );
    }

    /**
     * Check if this entry is a credit.
     */
    public function isCredit(): bool
    {
        return $this->transactionCode->isCredit();
    }

    /**
     * Check if this entry is a debit.
     */
    public function isDebit(): bool
    {
        return $this->transactionCode->isDebit();
    }

    /**
     * Check if this entry is a prenote.
     */
    public function isPrenote(): bool
    {
        return $this->transactionCode->isPrenote();
    }

    /**
     * Check if this entry has addenda.
     */
    public function hasAddenda(): bool
    {
        return $this->addenda !== null && $this->addenda !== '';
    }

    /**
     * Get the addenda indicator (0 or 1).
     */
    public function getAddendaIndicator(): int
    {
        return $this->hasAddenda() ? 1 : 0;
    }

    /**
     * Assign a trace number to this entry.
     */
    public function withTraceNumber(string $traceNumber): self
    {
        return new self(
            id: $this->id,
            transactionCode: $this->transactionCode,
            routingNumber: $this->routingNumber,
            accountNumber: $this->accountNumber,
            accountType: $this->accountType,
            amount: $this->amount,
            individualName: $this->individualName,
            individualId: $this->individualId,
            discretionaryData: $this->discretionaryData,
            addenda: $this->addenda,
            traceNumber: $traceNumber,
        );
    }

    /**
     * Get the amount in cents (for NACHA file format).
     */
    public function getAmountInCents(): int
    {
        return $this->amount->getAmountInCents();
    }

    /**
     * Get the formatted account number (max 17 chars, left-justified).
     */
    public function getFormattedAccountNumber(): string
    {
        return str_pad(mb_substr($this->accountNumber, 0, 17), 17);
    }

    /**
     * Get the formatted individual name (max 22 chars).
     */
    public function getFormattedIndividualName(): string
    {
        return str_pad(mb_substr($this->individualName, 0, 22), 22);
    }

    /**
     * Get the formatted individual ID (max 15 chars).
     */
    public function getFormattedIndividualId(): string
    {
        return str_pad(mb_substr($this->individualId, 0, 15), 15);
    }
}
