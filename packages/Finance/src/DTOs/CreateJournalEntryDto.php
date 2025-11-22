<?php

declare(strict_types=1);

namespace Nexus\Finance\DTOs;

use DateTimeImmutable;

/**
 * Data Transfer Object for creating journal entries.
 * 
 * Used to decouple Filament forms from domain entities.
 * Provides validation-friendly structure for UI layer with repeater support.
 */
final readonly class CreateJournalEntryDto
{
    /**
     * @param JournalEntryLineDto[] $lines
     */
    public function __construct(
        public DateTimeImmutable $entryDate,
        public string $description,
        public array $lines,
        public ?string $referenceNumber = null,
        public ?string $notes = null,
    ) {}

    /**
     * Create DTO from array (e.g., from Filament form data)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Convert line arrays to DTOs
        $lines = array_map(
            fn(array $line) => JournalEntryLineDto::fromArray($line),
            $data['lines'] ?? []
        );

        return new self(
            entryDate: new DateTimeImmutable($data['entry_date']),
            description: $data['description'],
            lines: $lines,
            referenceNumber: $data['reference_number'] ?? null,
            notes: $data['notes'] ?? null,
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
            'entry_date' => $this->entryDate->format('Y-m-d'),
            'description' => $this->description,
            'lines' => array_map(fn(JournalEntryLineDto $line) => $line->toArray(), $this->lines),
            'reference_number' => $this->referenceNumber,
            'notes' => $this->notes,
        ];
    }

    /**
     * Validate double-entry accounting rules
     * 
     * @return array<string> Validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        if (count($this->lines) < 2) {
            $errors[] = 'Journal entry must have at least 2 lines';
        }

        // Calculate debit and credit totals
        $debitTotal = '0';
        $creditTotal = '0';

        foreach ($this->lines as $line) {
            if ($line->isDebit) {
                $debitTotal = bcadd($debitTotal, $line->amount, 2);
            } else {
                $creditTotal = bcadd($creditTotal, $line->amount, 2);
            }
        }

        // Verify debits = credits
        if (bccomp($debitTotal, $creditTotal, 2) !== 0) {
            $errors[] = sprintf(
                'Debits (%s) must equal credits (%s)',
                $debitTotal,
                $creditTotal
            );
        }

        return $errors;
    }

    /**
     * Get total debits
     */
    public function getTotalDebits(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            if ($line->isDebit) {
                $total = bcadd($total, $line->amount, 2);
            }
        }
        return $total;
    }

    /**
     * Get total credits
     */
    public function getTotalCredits(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            if ($line->isCredit()) {
                $total = bcadd($total, $line->amount, 2);
            }
        }
        return $total;
    }

    /**
     * Check if journal entry is balanced
     */
    public function isBalanced(): bool
    {
        return bccomp($this->getTotalDebits(), $this->getTotalCredits(), 2) === 0;
    }
}
