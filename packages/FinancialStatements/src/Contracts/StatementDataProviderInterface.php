<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

use Nexus\FinancialStatements\ValueObjects\AccountBalance;
use Nexus\FinancialStatements\ValueObjects\StatementPeriod;

/**
 * Contract for providing financial data to statement builders.
 *
 * This interface is implemented by the orchestrator using Nexus\Finance
 * to fetch GL account balances and transaction data.
 */
interface StatementDataProviderInterface
{
    /**
     * Get account balances for a period.
     *
     * @param StatementPeriod $period The period to fetch balances for
     * @param array<string> $accountCodes Optional filter by account codes
     * @return array<AccountBalance>
     */
    public function getAccountBalances(StatementPeriod $period, array $accountCodes = []): array;

    /**
     * Get account balances by category.
     *
     * @param StatementPeriod $period
     * @param string $category Account category (assets, liabilities, equity, revenue, expenses)
     * @return array<AccountBalance>
     */
    public function getBalancesByCategory(StatementPeriod $period, string $category): array;

    /**
     * Get comparative balances for multiple periods.
     *
     * @param array<StatementPeriod> $periods
     * @return array<string, array<AccountBalance>>
     */
    public function getComparativeBalances(array $periods): array;
}
