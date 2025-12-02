<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\ValueObjects;

/**
 * Represents an account balance for statement generation.
 */
final readonly class AccountBalance
{
    public function __construct(
        private string $accountCode,
        private string $accountName,
        private string $accountCategory,
        private float $debitBalance,
        private float $creditBalance,
    ) {}

    public function getAccountCode(): string
    {
        return $this->accountCode;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getAccountCategory(): string
    {
        return $this->accountCategory;
    }

    public function getDebitBalance(): float
    {
        return $this->debitBalance;
    }

    public function getCreditBalance(): float
    {
        return $this->creditBalance;
    }

    public function getNetBalance(): float
    {
        return $this->debitBalance - $this->creditBalance;
    }
}
