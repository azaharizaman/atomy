<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\Enums\CashFlowMethod;

/**
 * Cash Flow Statement specific contract.
 *
 * Represents cash inflows and outflows from operating,
 * investing, and financing activities.
 */
interface CashFlowStatementInterface extends FinancialStatementInterface
{
    /**
     * Get the method used (Direct or Indirect).
     */
    public function getMethod(): CashFlowMethod;

    /**
     * Get net cash from operating activities.
     */
    public function getCashFromOperations(): float;

    /**
     * Get net cash from investing activities.
     */
    public function getCashFromInvesting(): float;

    /**
     * Get net cash from financing activities.
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
     * Verify cash reconciliation.
     */
    public function verifyCashReconciliation(): bool;
}
