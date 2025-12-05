<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

use Nexus\ChartOfAccount\Enums\AccountType;

/**
 * Data Transfer Object for account balance information.
 *
 * Used by DataProviders to transfer account balance data
 * from atomic packages to Coordinators and Services.
 */
final readonly class AccountBalanceDTO
{
    public function __construct(
        public string $accountId,
        public string $accountCode,
        public string $accountName,
        public AccountType $accountType,
        public string $debitBalance,
        public string $creditBalance,
        public string $currency,
    ) {}

    /**
     * Get the net balance (debit - credit).
     */
    public function getNetBalance(): string
    {
        return bcsub(
            $this->debitBalance,
            $this->creditBalance,
            2
        );
    }

    /**
     * Check if this is a debit balance account.
     */
    public function isDebitBalance(): bool
    {
        return bccomp($this->debitBalance, $this->creditBalance, 2) > 0;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'account_code' => $this->accountCode,
            'account_name' => $this->accountName,
            'debit_balance' => $this->debitBalance,
            'credit_balance' => $this->creditBalance,
            'currency' => $this->currency,
            'net_balance' => $this->getNetBalance(),
        ];
    }
}
