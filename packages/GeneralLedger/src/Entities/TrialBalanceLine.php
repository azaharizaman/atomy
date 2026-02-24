<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Entities;

use Nexus\Common\ValueObjects\Money;
use Nexus\GeneralLedger\Exceptions\InvalidTrialBalanceLineException;
use Nexus\GeneralLedger\Enums\BalanceType;

/**
 * Trial Balance Line
 * 
 * Represents a single line in a trial balance showing an account's balance.
 */
final readonly class TrialBalanceLine
{
    /**
     * @param string $ledgerAccountId Ledger account ULID
     * @param string $accountCode Account code
     * @param string $accountName Account name
     * @param string $currency Currency code
     * @param Money $debitBalance Debit balance (zero if credit balance)
     * @param Money $creditBalance Credit balance (zero if debit balance)
     */
    public function __construct(
        public string $ledgerAccountId,
        public string $accountCode,
        public string $accountName,
        public string $currency,
        public Money $debitBalance,
        public Money $creditBalance,
    ) {
        // Reject negative amounts
        if ($debitBalance->isNegative()) {
            throw new InvalidTrialBalanceLineException(
                sprintf('Debit balance for account %s cannot be negative', $this->accountCode)
            );
        }

        if ($creditBalance->isNegative()) {
            throw new InvalidTrialBalanceLineException(
                sprintf('Credit balance for account %s cannot be negative', $this->accountCode)
            );
        }

        // Either debit or credit should be positive, not both
        if ($debitBalance->isPositive() && $creditBalance->isPositive()) {
            throw new InvalidTrialBalanceLineException(
                'Account cannot have both debit and credit balance'
            );
        }

        // Validate currency consistency even when a balance is zero
        if ($debitBalance->getCurrency() !== $currency) {
            throw new InvalidTrialBalanceLineException(
                sprintf('Debit balance currency %s does not match line currency %s', $debitBalance->getCurrency(), $currency)
            );
        }

        if ($creditBalance->getCurrency() !== $currency) {
            throw new InvalidTrialBalanceLineException(
                sprintf('Credit balance currency %s does not match line currency %s', $creditBalance->getCurrency(), $currency)
            );
        }
    }

    /**
     * Get the net balance
     */
    public function getNetBalance(): Money
    {
        return $this->debitBalance->isPositive()
            ? $this->debitBalance
            : $this->creditBalance;
    }

    /**
     * Check if this is a debit balance
     */
    public function isDebit(): bool
    {
        return $this->debitBalance->isPositive();
    }

    /**
     * Check if this is a credit balance
     */
    public function isCredit(): bool
    {
        return $this->creditBalance->isPositive();
    }

    /**
     * Check if this account has no balance
     */
    public function isZero(): bool
    {
        return $this->debitBalance->isZero() && $this->creditBalance->isZero();
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'ledger_account_id' => $this->ledgerAccountId,
            'account_code' => $this->accountCode,
            'account_name' => $this->accountName,
            'currency' => $this->currency,
            'debit_balance' => $this->debitBalance->getAmount(),
            'credit_balance' => $this->creditBalance->getAmount(),
            'net_balance' => $this->getNetBalance()->getAmount(),
            'balance_type' => $this->isDebit() 
                ? BalanceType::DEBIT->value 
                : ($this->isCredit() ? BalanceType::CREDIT->value : BalanceType::NONE->value),
        ];
    }
}
