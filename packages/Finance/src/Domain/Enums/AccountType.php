<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Enums;

/**
 * Account Type Enum
 * 
 * Defines the five main account types in double-entry bookkeeping.
 */
enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return match($this) {
            self::Asset => 'Asset',
            self::Liability => 'Liability',
            self::Equity => 'Equity',
            self::Revenue => 'Revenue',
            self::Expense => 'Expense',
        };
    }

    /**
     * Check if this is a debit-normal account type
     */
    public function isDebitNormal(): bool
    {
        return match($this) {
            self::Asset, self::Expense => true,
            self::Liability, self::Equity, self::Revenue => false,
        };
    }

    /**
     * Check if this is a credit-normal account type
     */
    public function isCreditNormal(): bool
    {
        return !$this->isDebitNormal();
    }

    /**
     * Check if this is a balance sheet account
     */
    public function isBalanceSheet(): bool
    {
        return match($this) {
            self::Asset, self::Liability, self::Equity => true,
            self::Revenue, self::Expense => false,
        };
    }

    /**
     * Check if this is an income statement account
     */
    public function isIncomeStatement(): bool
    {
        return !$this->isBalanceSheet();
    }
}
