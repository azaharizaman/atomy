<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\Enums\CashFlowMethod;

/**
 * Statement generation engine contract.
 *
 * Responsible for building financial statements from ledger data.
 */
interface StatementBuilderInterface
{
    /**
     * Build a balance sheet for the specified period.
     *
     * @param array<string, mixed> $options Configuration options
     */
    public function buildBalanceSheet(
        string $entityId,
        ReportingPeriod $period,
        array $options = []
    ): BalanceSheetInterface;

    /**
     * Build an income statement for the specified period.
     *
     * @param array<string, mixed> $options Configuration options
     */
    public function buildIncomeStatement(
        string $entityId,
        ReportingPeriod $period,
        array $options = []
    ): IncomeStatementInterface;

    /**
     * Build a cash flow statement for the specified period.
     *
     * @param array<string, mixed> $options Configuration options
     */
    public function buildCashFlowStatement(
        string $entityId,
        ReportingPeriod $period,
        CashFlowMethod $method,
        array $options = []
    ): CashFlowStatementInterface;

    /**
     * Build a comparative statement with multiple periods.
     *
     * @param ReportingPeriod[] $periods
     * @param array<string, mixed> $options
     * @return FinancialStatementInterface[]
     */
    public function buildComparativeStatement(
        string $entityId,
        array $periods,
        array $options = []
    ): array;
}
