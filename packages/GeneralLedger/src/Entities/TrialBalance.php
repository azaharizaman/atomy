<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Entities;

use Nexus\Common\ValueObjects\Money;

/**
 * Trial Balance Entity
 * 
 * Represents a trial balance report - a snapshot of all account balances
 * at a specific point in time. The trial balance verifies that debits
 * equal credits by listing all accounts and their debit or credit balances.
 * 
 * This is a fundamental accounting report used to:
 * - Verify mathematical accuracy of the general ledger
 * - Identify accounts with balances for financial statement preparation
 * - Support period-end closing procedures
 */
final readonly class TrialBalance
{
    /**
     * @param string $id Unique identifier (ULID)
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Fiscal period ULID
     * @param \DateTimeImmutable $asOfDate Date the trial balance is as of
     * @param array<TrialBalanceLine> $lines Individual account lines
     * @param Money $totalDebits Sum of all debit balances
     * @param Money $totalCredits Sum of all credit balances
     * @param bool $isBalanced Whether total debits equal total credits
     * @param \DateTimeImmutable $generatedAt When the trial balance was generated
     */
    public function __construct(
        public string $id,
        public string $ledgerId,
        public string $periodId,
        public \DateTimeImmutable $asOfDate,
        public array $lines,
        public Money $totalDebits,
        public Money $totalCredits,
        public bool $isBalanced,
        public \DateTimeImmutable $generatedAt,
    ) {
        // Validate that we have lines
        if (empty($this->lines)) {
            throw new \InvalidArgumentException(
                'Trial balance must have at least one line'
            );
        }

        // Validate balance
        if ($this->totalDebits->getCurrency() !== $this->totalCredits->getCurrency()) {
            throw new \InvalidArgumentException(
                'Debits and credits must have the same currency'
            );
        }

        // Validate isBalanced flag
        $actualBalance = $this->totalDebits->subtract($this->totalCredits);
        if ($actualBalance->isZero() !== $this->isBalanced) {
            throw new \InvalidArgumentException(
                sprintf(
                    'isBalanced flag is inconsistent. Debits: %s, Credits: %s',
                    $this->totalDebits->getAmount(),
                    $this->totalCredits->getAmount()
                )
            );
        }
    }

    /**
     * Create a trial balance from lines
     * 
     * This factory method calculates totals and balance status from the lines.
     */
    public static function create(
        string $id,
        string $ledgerId,
        string $periodId,
        \DateTimeImmutable $asOfDate,
        array $lines,
    ): self {
        $currency = match (count($lines)) {
            0 => 'USD',
            default => $lines[0]->currency,
        };

        $totalDebits = Money::zero($currency);
        $totalCredits = Money::zero($currency);

        foreach ($lines as $line) {
            if ($line->debitBalance->isPositive()) {
                $totalDebits = $totalDebits->add($line->debitBalance);
            } elseif ($line->creditBalance->isPositive()) {
                $totalCredits = $totalCredits->add($line->creditBalance);
            }
        }

        $difference = $totalDebits->subtract($totalCredits);
        $isBalanced = $difference->isZero();

        return new self(
            id: $id,
            ledgerId: $ledgerId,
            periodId: $periodId,
            asOfDate: $asOfDate,
            lines: $lines,
            totalDebits: $totalDebits,
            totalCredits: $totalCredits,
            isBalanced: $isBalanced,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get the difference between debits and credits
     */
    public function getDifference(): Money
    {
        return $this->totalDebits->subtract($this->totalCredits);
    }

    /**
     * Get the number of accounts with debit balances
     */
    public function getDebitCount(): int
    {
        return count(array_filter($this->lines, fn($line) => $line->debitBalance->isPositive()));
    }

    /**
     * Get the number of accounts with credit balances
     */
    public function getCreditCount(): int
    {
        return count(array_filter($this->lines, fn($line) => $line->creditBalance->isPositive()));
    }

    /**
     * Get accounts with debit balances
     */
    public function getDebitAccounts(): array
    {
        return array_filter($this->lines, fn($line) => $line->debitBalance->isPositive());
    }

    /**
     * Get accounts with credit balances
     */
    public function getCreditAccounts(): array
    {
        return array_filter($this->lines, fn($line) => $line->creditBalance->isPositive());
    }

    /**
     * Get a summary of the trial balance
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'ledger_id' => $this->ledgerId,
            'period_id' => $this->periodId,
            'as_of_date' => $this->asOfDate->format('Y-m-d'),
            'total_debits' => $this->totalDebits->getAmount(),
            'total_credits' => $this->totalCredits->getAmount(),
            'difference' => $this->getDifference()->getAmount(),
            'is_balanced' => $this->isBalanced,
            'account_count' => count($this->lines),
            'debit_count' => $this->getDebitCount(),
            'credit_count' => $this->getCreditCount(),
            'generated_at' => $this->generatedAt->format(\DateTimeInterface::ISO8601),
        ];
    }
}

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
        // Either debit or credit should be positive, not both
        if ($debitBalance->isPositive() && $creditBalance->isPositive()) {
            throw new \InvalidArgumentException(
                'Account cannot have both debit and credit balance'
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
            'balance_type' => $this->isDebit() ? 'debit' : ($this->isCredit() ? 'credit' : 'zero'),
        ];
    }
}
