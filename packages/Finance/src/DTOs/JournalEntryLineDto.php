<?php

declare(strict_types=1);

namespace Nexus\Finance\DTOs;

/**
 * Data Transfer Object for journal entry lines.
 * 
 * Represents a single debit or credit line in a journal entry.
 * Used to decouple Filament repeater forms from domain entities.
 */
final readonly class JournalEntryLineDto
{
    public function __construct(
        public string $accountId,
        public string $amount,
        public bool $isDebit,
        public ?string $description = null,
    ) {}

    /**
     * Create DTO from array (e.g., from Filament repeater)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: $data['account_id'],
            amount: $data['amount'],
            isDebit: $data['is_debit'] ?? false,
            description: $data['description'] ?? null,
        );
    }

    /**
     * Convert to array for validation or serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'amount' => $this->amount,
            'is_debit' => $this->isDebit,
            'description' => $this->description,
        ];
    }

    /**
     * Check if this is a credit line
     */
    public function isCredit(): bool
    {
        return !$this->isDebit;
    }
}
