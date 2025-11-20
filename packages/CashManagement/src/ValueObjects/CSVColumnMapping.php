<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use InvalidArgumentException;

/**
 * CSV Column Mapping Value Object
 *
 * Encapsulates the mapping configuration for CSV bank statement imports.
 */
final readonly class CSVColumnMapping
{
    public function __construct(
        private string $dateColumn,
        private string $descriptionColumn,
        private ?string $debitColumn = null,
        private ?string $creditColumn = null,
        private ?string $amountColumn = null,
        private ?string $balanceColumn = null,
        private ?string $referenceColumn = null,
        private string $dateFormat = 'Y-m-d'
    ) {
        $this->validate();
    }

    /**
     * Validate column mapping configuration
     */
    private function validate(): void
    {
        if (empty($this->dateColumn)) {
            throw new InvalidArgumentException('Date column is required');
        }

        if (empty($this->descriptionColumn)) {
            throw new InvalidArgumentException('Description column is required');
        }

        // Must not provide only one of debit or credit column
        if (($this->debitColumn !== null) !== ($this->creditColumn !== null)) {
            throw new InvalidArgumentException('Must provide both debit and credit columns together, not just one');
        }
        // Must have either BOTH debit AND credit columns OR amount column
        $hasDebitCredit = $this->debitColumn !== null && $this->creditColumn !== null;
        $hasAmount = $this->amountColumn !== null;

        if (!$hasDebitCredit && !$hasAmount) {
            throw new InvalidArgumentException('Must specify either both debit and credit columns or amount column');
        }

        if ($hasDebitCredit && $hasAmount) {
            throw new InvalidArgumentException('Cannot specify both debit/credit columns and amount column');
        }
    }

    public function getDateColumn(): string
    {
        return $this->dateColumn;
    }

    public function getDescriptionColumn(): string
    {
        return $this->descriptionColumn;
    }

    public function getDebitColumn(): ?string
    {
        return $this->debitColumn;
    }

    public function getCreditColumn(): ?string
    {
        return $this->creditColumn;
    }

    public function getAmountColumn(): ?string
    {
        return $this->amountColumn;
    }

    public function getBalanceColumn(): ?string
    {
        return $this->balanceColumn;
    }

    public function getReferenceColumn(): ?string
    {
        return $this->referenceColumn;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Check if using debit/credit format
     */
    public function hasDebitCreditColumns(): bool
    {
        return $this->debitColumn !== null && $this->creditColumn !== null;
    }

    /**
     * Check if using amount format
     */
    public function hasAmountColumn(): bool
    {
        return $this->amountColumn !== null;
    }

    /**
     * Convert to array for JSON storage
     */
    public function toArray(): array
    {
        return [
            'date_column' => $this->dateColumn,
            'description_column' => $this->descriptionColumn,
            'debit_column' => $this->debitColumn,
            'credit_column' => $this->creditColumn,
            'amount_column' => $this->amountColumn,
            'balance_column' => $this->balanceColumn,
            'reference_column' => $this->referenceColumn,
            'date_format' => $this->dateFormat,
        ];
    }

    /**
     * Create from array (for loading from JSON)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dateColumn: $data['date_column'],
            descriptionColumn: $data['description_column'],
            debitColumn: $data['debit_column'] ?? null,
            creditColumn: $data['credit_column'] ?? null,
            amountColumn: $data['amount_column'] ?? null,
            balanceColumn: $data['balance_column'] ?? null,
            referenceColumn: $data['reference_column'] ?? null,
            dateFormat: $data['date_format'] ?? 'Y-m-d'
        );
    }
}
