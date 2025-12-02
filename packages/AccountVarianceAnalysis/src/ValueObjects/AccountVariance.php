<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\ValueObjects;

/**
 * Variance for a specific account.
 */
final readonly class AccountVariance
{
    public function __construct(
        private string $accountCode,
        private string $accountName,
        private VarianceResult $result,
        private ?string $category = null,
    ) {}

    public function getAccountCode(): string
    {
        return $this->accountCode;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getResult(): VarianceResult
    {
        return $this->result;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }
}
