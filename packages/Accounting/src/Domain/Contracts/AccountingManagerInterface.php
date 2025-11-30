<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use DateTimeImmutable;
use Nexus\Accounting\Domain\Entities\BalanceSheet;
use Nexus\Accounting\Domain\Entities\CashFlowStatement;
use Nexus\Accounting\Domain\Entities\ConsolidatedStatement;
use Nexus\Accounting\Domain\Entities\IncomeStatement;
use Nexus\Accounting\Domain\Entities\PeriodCloseRecord;
use Nexus\Accounting\Domain\Exceptions\ConsolidationException;
use Nexus\Accounting\Domain\Exceptions\PeriodCloseException;
use Nexus\Accounting\Domain\Exceptions\StatementGenerationException;

/**
 * Primary interface for accounting operations.
 *
 * Provides high-level operations for financial statement generation,
 * period close processing, and financial consolidation.
 */
interface AccountingManagerInterface
{
    /**
     * Generate an income statement (P&L) for a period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param array<string, mixed> $options Generation options
     * @return IncomeStatement
     * @throws StatementGenerationException If generation fails
     */
    public function generateIncomeStatement(
        string $tenantId,
        string $periodId,
        array $options = []
    ): IncomeStatement;

    /**
     * Generate a balance sheet as of a specific date.
     *
     * @param string $tenantId Tenant identifier
     * @param DateTimeImmutable $asOfDate Balance sheet date
     * @param array<string, mixed> $options Generation options
     * @return BalanceSheet
     * @throws StatementGenerationException If generation fails
     */
    public function generateBalanceSheet(
        string $tenantId,
        DateTimeImmutable $asOfDate,
        array $options = []
    ): BalanceSheet;

    /**
     * Generate a cash flow statement for a period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param array<string, mixed> $options Generation options
     * @return CashFlowStatement
     * @throws StatementGenerationException If generation fails
     */
    public function generateCashFlowStatement(
        string $tenantId,
        string $periodId,
        array $options = []
    ): CashFlowStatement;

    /**
     * Close a fiscal period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param string $closedBy User who closed the period
     * @return PeriodCloseRecord
     * @throws PeriodCloseException If close fails
     */
    public function closePeriod(
        string $tenantId,
        string $periodId,
        string $closedBy
    ): PeriodCloseRecord;

    /**
     * Reopen a closed period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param string $reopenedBy User who reopened the period
     * @param string $reason Reason for reopening
     * @throws PeriodCloseException If reopen fails
     */
    public function reopenPeriod(
        string $tenantId,
        string $periodId,
        string $reopenedBy,
        string $reason
    ): void;

    /**
     * Check if a period is closed.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return bool
     */
    public function isPeriodClosed(string $tenantId, string $periodId): bool;

    /**
     * Generate consolidated financial statements.
     *
     * @param string $parentTenantId Parent company tenant ID
     * @param array<string> $subsidiaryTenantIds Subsidiary tenant IDs
     * @param string $periodId Period identifier
     * @param string $statementType Type of statement to consolidate
     * @return ConsolidatedStatement
     * @throws ConsolidationException If consolidation fails
     */
    public function consolidate(
        string $parentTenantId,
        array $subsidiaryTenantIds,
        string $periodId,
        string $statementType
    ): ConsolidatedStatement;
}
