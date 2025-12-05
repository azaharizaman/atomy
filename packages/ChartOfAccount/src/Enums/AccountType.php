<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Enums;

/**
 * Account Type enumeration.
 *
 * Defines the five standard account types used in double-entry bookkeeping.
 * Each type has a normal balance (debit or credit) that determines how
 * increases and decreases are recorded.
 *
 * Balance Sheet Accounts:
 * - Asset: Debit normal (increases with debits)
 * - Liability: Credit normal (increases with credits)
 * - Equity: Credit normal (increases with credits)
 *
 * Income Statement Accounts:
 * - Revenue: Credit normal (increases with credits)
 * - Expense: Debit normal (increases with debits)
 */
enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    /**
     * Check if this account type has a normal debit balance.
     *
     * Assets and Expenses increase with debits and decrease with credits.
     */
    public function isDebitNormal(): bool
    {
        return match ($this) {
            self::Asset, self::Expense => true,
            self::Liability, self::Equity, self::Revenue => false,
        };
    }

    /**
     * Check if this account type has a normal credit balance.
     *
     * Liabilities, Equity, and Revenues increase with credits and decrease with debits.
     */
    public function isCreditNormal(): bool
    {
        return !$this->isDebitNormal();
    }

    /**
     * Check if this account type appears on the Balance Sheet.
     *
     * Assets, Liabilities, and Equity are permanent accounts on the Balance Sheet.
     */
    public function isBalanceSheetAccount(): bool
    {
        return match ($this) {
            self::Asset, self::Liability, self::Equity => true,
            self::Revenue, self::Expense => false,
        };
    }

    /**
     * Check if this account type appears on the Income Statement.
     *
     * Revenues and Expenses are temporary accounts on the Income Statement.
     */
    public function isIncomeStatementAccount(): bool
    {
        return !$this->isBalanceSheetAccount();
    }

    /**
     * Get a human-readable label for this account type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Asset => 'Asset',
            self::Liability => 'Liability',
            self::Equity => 'Equity',
            self::Revenue => 'Revenue',
            self::Expense => 'Expense',
        };
    }

    /**
     * Get the typical starting number range for this account type.
     *
     * This follows common accounting conventions:
     * - 1000-1999: Assets
     * - 2000-2999: Liabilities
     * - 3000-3999: Equity
     * - 4000-4999: Revenue
     * - 5000-5999: Expenses (sometimes 6000-9999)
     */
    public function getTypicalCodeRange(): array
    {
        return match ($this) {
            self::Asset => ['start' => '1000', 'end' => '1999'],
            self::Liability => ['start' => '2000', 'end' => '2999'],
            self::Equity => ['start' => '3000', 'end' => '3999'],
            self::Revenue => ['start' => '4000', 'end' => '4999'],
            self::Expense => ['start' => '5000', 'end' => '9999'],
        };
    }
}
