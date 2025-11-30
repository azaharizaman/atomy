<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Enums\CashFlowMethod;

/**
 * Cash Flow Statement interface.
 *
 * Supports both direct and indirect methods.
 */
interface CashFlowStatementInterface extends FinancialStatementInterface
{
    /**
     * Get the method used (direct or indirect).
     */
    public function getMethod(): CashFlowMethod;

    /**
     * Get cash flow from operating activities.
     */
    public function getCashFromOperations(): float;

    /**
     * Get cash flow from investing activities.
     */
    public function getCashFromInvesting(): float;

    /**
     * Get cash flow from financing activities.
     */
    public function getCashFromFinancing(): float;

    /**
     * Get net change in cash.
     */
    public function getNetCashChange(): float;

    /**
     * Get beginning cash balance.
     */
    public function getBeginningCash(): float;

    /**
     * Get ending cash balance.
     */
    public function getEndingCash(): float;

    /**
     * Verify that beginning + net change = ending.
     */
    public function verifyCashReconciliation(): bool;
}
