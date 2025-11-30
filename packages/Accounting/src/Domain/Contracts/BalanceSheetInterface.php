<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

/**
 * Balance Sheet interface.
 *
 * Extends FinancialStatementInterface with balance sheet specific methods.
 */
interface BalanceSheetInterface extends FinancialStatementInterface
{
    /**
     * Get total assets.
     */
    public function getTotalAssets(): float;

    /**
     * Get total current assets.
     */
    public function getTotalCurrentAssets(): float;

    /**
     * Get total non-current assets.
     */
    public function getTotalNonCurrentAssets(): float;

    /**
     * Get total liabilities.
     */
    public function getTotalLiabilities(): float;

    /**
     * Get total current liabilities.
     */
    public function getTotalCurrentLiabilities(): float;

    /**
     * Get total non-current liabilities.
     */
    public function getTotalNonCurrentLiabilities(): float;

    /**
     * Get total equity.
     */
    public function getTotalEquity(): float;

    /**
     * Verify that Assets = Liabilities + Equity.
     */
    public function verifyBalance(): bool;

    /**
     * Get working capital (Current Assets - Current Liabilities).
     */
    public function getWorkingCapital(): float;
}
