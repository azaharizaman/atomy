<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\BalanceSheet;
use Nexus\Accounting\Domain\Entities\CashFlowStatement;
use Nexus\Accounting\Domain\Entities\IncomeStatement;
use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;

/**
 * Contract for generating financial statements.
 *
 * Implementations must generate complete, accurate financial statements
 * from underlying accounting data. All statements must comply with
 * applicable accounting standards (GAAP, IFRS, or as configured).
 */
interface FinancialStatementGeneratorInterface
{
    /**
     * Generate an income statement for the specified period.
     *
     * @param ReportingPeriod $period The reporting period for the statement
     * @param array<string, mixed> $options Additional generation options
     *
     * @return IncomeStatement The generated income statement
     *
     * @throws \RuntimeException If statement generation fails
     */
    public function generateIncomeStatement(
        ReportingPeriod $period,
        array $options = []
    ): IncomeStatement;

    /**
     * Generate a balance sheet as of the specified date.
     *
     * @param \DateTimeImmutable $asOfDate The date for the balance sheet
     * @param array<string, mixed> $options Additional generation options
     *
     * @return BalanceSheet The generated balance sheet
     *
     * @throws \RuntimeException If statement generation fails
     */
    public function generateBalanceSheet(
        \DateTimeImmutable $asOfDate,
        array $options = []
    ): BalanceSheet;

    /**
     * Generate a cash flow statement for the specified period.
     *
     * @param ReportingPeriod $period The reporting period for the statement
     * @param array<string, mixed> $options Additional generation options
     *
     * @return CashFlowStatement The generated cash flow statement
     *
     * @throws \RuntimeException If statement generation fails
     */
    public function generateCashFlowStatement(
        ReportingPeriod $period,
        array $options = []
    ): CashFlowStatement;
}
