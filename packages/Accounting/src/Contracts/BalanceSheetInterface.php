<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

/**
 * Balance Sheet specific contract.
 *
 * Represents a snapshot of assets, liabilities, and equity
 * at a specific point in time.
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
     * Verify the accounting equation: Assets = Liabilities + Equity.
     */
    public function verifyBalance(): bool;

    /**
     * Get working capital (Current Assets - Current Liabilities).
     */
    public function getWorkingCapital(): float;
}
