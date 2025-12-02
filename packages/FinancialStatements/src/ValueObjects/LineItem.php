<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\ValueObjects;

/**
 * Represents a line item in a financial statement.
 */
final readonly class LineItem
{
    public function __construct(
        private string $accountCode,
        private string $accountName,
        private float $amount,
        private ?string $description = null,
    ) {}

    public function getAccountCode(): string
    {
        return $this->accountCode;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
