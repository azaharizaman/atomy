<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\Enums\CashFlowMethod;

/**
 * Statement Builder interface.
 *
 * Builds financial statements from ledger data.
 */
interface StatementBuilderInterface
{
    /**
     * Build a Balance Sheet for the specified entity and period.
     *
     * @param array<string, mixed> $options
     */
    public function buildBalanceSheet(
        string $entityId,
        ReportingPeriod $period,
        array $options = []
    ): BalanceSheetInterface;

    /**
     * Build an Income Statement for the specified entity and period.
     *
     * @param array<string, mixed> $options
     */
    public function buildIncomeStatement(
        string $entityId,
        ReportingPeriod $period,
        array $options = []
    ): IncomeStatementInterface;

    /**
     * Build a Cash Flow Statement for the specified entity and period.
     *
     * @param array<string, mixed> $options
     */
    public function buildCashFlowStatement(
        string $entityId,
        ReportingPeriod $period,
        CashFlowMethod $method,
        array $options = []
    ): CashFlowStatementInterface;

    /**
     * Build comparative statements across multiple periods.
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
