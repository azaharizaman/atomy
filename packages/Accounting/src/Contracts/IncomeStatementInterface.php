<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

/**
 * Income Statement (Profit & Loss) specific contract.
 *
 * Represents revenues, expenses, and net income
 * for a period of time.
 */
interface IncomeStatementInterface extends FinancialStatementInterface
{
    /**
     * Get total revenue.
     */
    public function getTotalRevenue(): float;

    /**
     * Get cost of goods sold.
     */
    public function getCostOfGoodsSold(): float;

    /**
     * Get gross profit (Revenue - COGS).
     */
    public function getGrossProfit(): float;

    /**
     * Get gross profit margin percentage.
     */
    public function getGrossProfitMargin(): float;

    /**
     * Get total operating expenses.
     */
    public function getTotalOperatingExpenses(): float;

    /**
     * Get operating income (Gross Profit - Operating Expenses).
     */
    public function getOperatingIncome(): float;

    /**
     * Get other income/expenses (non-operating).
     */
    public function getOtherIncome(): float;

    /**
     * Get income before tax.
     */
    public function getIncomeBeforeTax(): float;

    /**
     * Get tax expense.
     */
    public function getTaxExpense(): float;

    /**
     * Get net income (bottom line).
     */
    public function getNetIncome(): float;

    /**
     * Get earnings per share (if applicable).
     */
    public function getEarningsPerShare(): ?float;
}
