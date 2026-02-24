<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Entities;

use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Brick\Math\BigDecimal;

/**
 * Transaction Entity
 * 
 * Represents a single debit or credit entry posted to a ledger account.
 * Each transaction records the amount, type, running balance, and links
 * back to the source document (journal entry line).
 * 
 * The running balance is calculated based on the account's balance type:
 * - Debit-balanced accounts (Assets, Expenses): Debits increase, Credits decrease
 * - Credit-balanced accounts (Liabilities, Equity, Revenue): Credits increase, Debits decrease
 */
final readonly class Transaction
{
    /**
     * @param string $id Unique identifier (ULID)
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $journalEntryLineId Source journal entry line ULID
     * @param string $journalEntryId Parent journal entry ULID
     * @param TransactionType $type DEBIT or CREDIT
     * @param AccountBalance $amount Transaction amount with debit/credit indicator
     * @param AccountBalance $runningBalance Balance after this transaction
     * @param string $periodId Fiscal period ULID
     * @param \DateTimeImmutable $postingDate Date when transaction was posted
     * @param \DateTimeImmutable $transactionDate Date of the source document
     * @param \DateTimeImmutable $createdAt Timestamp of creation
     * @param string|null $description Transaction description (from journal entry)
     * @param string|null $reference External reference (invoice number, check number, etc.)
     * @param string|null $reversedById Transaction ID that reverses this transaction (if any)
     */
    public function __construct(
        public string $id,
        public string $ledgerAccountId,
        public string $journalEntryLineId,
        public string $journalEntryId,
        public TransactionType $type,
        public AccountBalance $amount,
        public AccountBalance $runningBalance,
        public string $periodId,
        public \DateTimeImmutable $postingDate,
        public \DateTimeImmutable $transactionDate,
        public \DateTimeImmutable $createdAt,
        public ?string $description = null,
        public ?string $reference = null,
        public ?string $reversedById = null,
    ) {
        // Validate that amount is positive
        if (!$this->amount->getAmount()->isPositive()) {
            throw new \InvalidArgumentException(
                'Transaction amount must be positive'
            );
        }

        // Validate transaction date is not in the future
        $now = new \DateTimeImmutable();
        if ($this->transactionDate > $now) {
            throw new \InvalidArgumentException(
                'Transaction date cannot be in the future'
            );
        }

        // Validate posting date is not in the future
        if ($this->postingDate > $now) {
            throw new \InvalidArgumentException(
                'Posting date cannot be in the future'
            );
        }
    }

    /**
     * Create a new transaction
     */
    public static function create(
        string $id,
        string $ledgerAccountId,
        string $journalEntryLineId,
        string $journalEntryId,
        TransactionType $type,
        AccountBalance $amount,
        AccountBalance $runningBalance,
        string $periodId,
        \DateTimeImmutable $postingDate,
        \DateTimeImmutable $transactionDate,
        ?string $description = null,
        ?string $reference = null,
    ): self {
        return new self(
            id: $id,
            ledgerAccountId: $ledgerAccountId,
            journalEntryLineId: $journalEntryLineId,
            journalEntryId: $journalEntryId,
            type: $type,
            amount: $amount,
            runningBalance: $runningBalance,
            periodId: $periodId,
            postingDate: $postingDate,
            transactionDate: $transactionDate,
            createdAt: new \DateTimeImmutable(),
            description: $description,
            reference: $reference,
            reversedById: null,
        );
    }

    /**
     * Check if this is a debit transaction
     */
    public function isDebit(): bool
    {
        return $this->type === TransactionType::DEBIT;
    }

    /**
     * Check if this is a credit transaction
     */
    public function isCredit(): bool
    {
        return $this->type === TransactionType::CREDIT;
    }

    /**
     * Check if this transaction has been reversed
     */
    public function isReversed(): bool
    {
        return $this->reversedById !== null;
    }

    /**
     * Check if this transaction can be reversed
     * 
     * A transaction can only be reversed if it hasn't been reversed already.
     */
    public function canReverse(): bool
    {
        return !$this->isReversed();
    }

    /**
     * Create a reversed version of this transaction
     * 
     * The reversed transaction will have the opposite type (debit becomes credit
     * and vice versa) and the same amount. The caller is responsible for
     * calculating the correct running balance using BalanceCalculationService
     * before creating the reversal transaction.
     *
     * @param string $reversalId The ID for the reversal transaction
     * @param string $reversalPeriodId The period ID for the reversal
     * @param AccountBalance $runningBalance The pre-calculated running balance
     * @throws \RuntimeException If the transaction has already been reversed
     */
    public function reverse(string $reversalId, string $reversalPeriodId, AccountBalance $runningBalance): array
    {
        if (!$this->canReverse()) {
            throw new \RuntimeException(
                sprintf('Transaction %s has already been reversed', $this->id)
            );
        }

        // Create the reversal transaction with opposite type
        $reversalType = $this->type->opposite();
        
        $reversalTransaction = new self(
            id: $reversalId,
            ledgerAccountId: $this->ledgerAccountId,
            journalEntryLineId: $this->journalEntryLineId,
            journalEntryId: $this->journalEntryId,
            type: $reversalType,
            amount: $this->amount,
            runningBalance: $runningBalance,
            periodId: $reversalPeriodId,
            postingDate: new \DateTimeImmutable(),
            transactionDate: $this->transactionDate,
            createdAt: new \DateTimeImmutable(),
            description: 'Reversal: ' . ($this->description ?? $this->id),
            reference: 'Reversal of ' . $this->id,
            reversedById: null,
        );

        // Return array with [reversalTransaction, originalWithReversalReference]
        $originalReversed = new self(
            id: $this->id,
            ledgerAccountId: $this->ledgerAccountId,
            journalEntryLineId: $this->journalEntryLineId,
            journalEntryId: $this->journalEntryId,
            type: $this->type,
            amount: $this->amount,
            runningBalance: $this->runningBalance,
            periodId: $this->periodId,
            postingDate: $this->postingDate,
            transactionDate: $this->transactionDate,
            createdAt: $this->createdAt,
            description: $this->description,
            reference: $this->reference,
            reversedById: $reversalId,
        );

        return [$reversalTransaction, $originalReversed];
    }

    /**
     * Get the effective balance impact
     * 
     * This returns the amount with the correct sign based on transaction type
     * and account balance type. For debit-balanced accounts, debits are positive.
     * For credit-balanced accounts, credits are positive.
     *
     * @param BalanceType $balanceType The balance type of the account
     */
    public function getEffectiveBalanceImpact(BalanceType $balanceType): BigDecimal
    {
        return match ($this->type) {
            TransactionType::DEBIT => $balanceType->isDebit() 
                ? BigDecimal::of($this->amount->getAmountInMinorUnits()) 
                : BigDecimal::of(-$this->amount->getAmountInMinorUnits()),
            TransactionType::CREDIT => $balanceType->isCredit() 
                ? BigDecimal::of($this->amount->getAmountInMinorUnits()) 
                : BigDecimal::of(-$this->amount->getAmountInMinorUnits()),
        };
    }

    /**
     * Get a summary of the transaction for logging/display
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'ledgerAccountId' => $this->ledgerAccountId,
            'type' => $this->type->value,
            'amount' => $this->amount->getAmount()->getAmount(),
            'runningBalance' => $this->runningBalance->getAmount()->getAmount(),
            'periodId' => $this->periodId,
            'postingDate' => $this->postingDate->format('Y-m-d'),
            'transactionDate' => $this->transactionDate->format('Y-m-d'),
            'isReversed' => $this->isReversed(),
        ];
    }
}
