<?php

declare(strict_types=1);

namespace Nexus\CashManagement\DTOs;

use DateTimeImmutable;

/**
 * Statement Line Data Transfer Object
 *
 * Standardized output from Import package for bank statement lines.
 */
final readonly class StatementLineDTO
{
    public function __construct(
        public DateTimeImmutable $date,
        public string $description,
        public string $amount,
        public ?string $balance = null,
        public ?string $reference = null,
        public ?string $transactionType = null
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: new DateTimeImmutable($data['date']),
            description: $data['description'],
            amount: $data['amount'],
            balance: $data['balance'] ?? null,
            reference: $data['reference'] ?? null,
            transactionType: $data['transaction_type'] ?? null
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'description' => $this->description,
            'amount' => $this->amount,
            'balance' => $this->balance,
            'reference' => $this->reference,
            'transaction_type' => $this->transactionType,
        ];
    }
}
