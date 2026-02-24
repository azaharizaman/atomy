<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\ValueObjects;

use Nexus\GeneralLedger\Enums\TransactionType;

/**
 * Transaction Detail Value Object
 * 
 * Represents a complete transaction line with all metadata needed for
 * recording a GL transaction. This includes the account, amount, type,
 * and reference information.
 */
final readonly class TransactionDetail
{
    /**
     * @param string $ledgerAccountId Ledger account ULID
     * @param TransactionType $type Transaction type (DEBIT or CREDIT)
     * @param AccountBalance $amount Transaction amount with balance type
     * @param string|null $journalEntryLineId Source journal entry line ULID
     * @param string|null $description Transaction description
     * @param string|null $reference External reference (invoice number, etc.)
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $ledgerAccountId,
        public TransactionType $type,
        public AccountBalance $amount,
        public ?string $journalEntryLineId = null,
        public ?string $description = null,
        public ?string $reference = null,
        public array $metadata = [],
    ) {
        if ($this->amount->isZero()) {
            throw new \InvalidArgumentException(
                'Transaction amount cannot be zero'
            );
        }
    }

    /**
     * Create a debit transaction detail
     */
    public static function debit(
        string $ledgerAccountId,
        AccountBalance $amount,
        ?string $journalEntryLineId = null,
        ?string $description = null,
        ?string $reference = null,
        array $metadata = [],
    ): self {
        return new self(
            ledgerAccountId: $ledgerAccountId,
            type: TransactionType::DEBIT,
            amount: $amount,
            journalEntryLineId: $journalEntryLineId,
            description: $description,
            reference: $reference,
            metadata: $metadata,
        );
    }

    /**
     * Create a credit transaction detail
     */
    public static function credit(
        string $ledgerAccountId,
        AccountBalance $amount,
        ?string $journalEntryLineId = null,
        ?string $description = null,
        ?string $reference = null,
        array $metadata = [],
    ): self {
        return new self(
            ledgerAccountId: $ledgerAccountId,
            type: TransactionType::CREDIT,
            amount: $amount,
            journalEntryLineId: $journalEntryLineId,
            description: $description,
            reference: $reference,
            metadata: $metadata,
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
     * Get the transaction amount
     */
    public function getAmount(): AccountBalance
    {
        return $this->amount;
    }

    /**
     * Get metadata value by key
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'ledger_account_id' => $this->ledgerAccountId,
            'type' => $this->type->value,
            'amount' => $this->amount->toArray(),
            'journal_entry_line_id' => $this->journalEntryLineId,
            'description' => $this->description,
            'reference' => $this->reference,
            'metadata' => $this->metadata,
        ];
    }
}
