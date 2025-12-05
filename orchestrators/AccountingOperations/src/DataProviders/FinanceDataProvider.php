<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DataProviders;

use Nexus\AccountingOperations\DTOs\AccountBalanceDTO;
use Nexus\ChartOfAccount\Contracts\AccountQueryInterface;
use Nexus\JournalEntry\Contracts\LedgerQueryInterface;

/**
 * Data provider that integrates with ChartOfAccount and JournalEntry packages.
 *
 * Aggregates account and balance data from atomic packages for statement generation.
 */
final readonly class FinanceDataProvider
{
    public function __construct(
        private AccountQueryInterface $accountQuery,
        private LedgerQueryInterface $ledgerQuery,
    ) {}

    /**
     * Get account balances for a period.
     *
     * @return array<AccountBalanceDTO>
     */
    public function getAccountBalances(string $tenantId, string $periodId): array
    {
        $accounts = $this->accountQuery->findAll();
        $balances = [];

        foreach ($accounts as $account) {
            $balance = $this->ledgerQuery->getAccountBalance(
                accountId: $account->getId(),
                asOfDate: new \DateTimeImmutable()
            );

            // Balance is a Money object - positive = debit, negative = credit
            $amount = $balance->getAmount();
            $debitBalance = $amount >= 0 ? number_format($amount, 2, '.', '') : '0.00';
            $creditBalance = $amount < 0 ? number_format(abs($amount), 2, '.', '') : '0.00';

            $balances[] = new AccountBalanceDTO(
                accountId: $account->getId(),
                accountCode: $account->getCode(),
                accountName: $account->getName(),
                accountType: $account->getType(),
                debitBalance: $debitBalance,
                creditBalance: $creditBalance,
                currency: $balance->getCurrency()
            );
        }

        return $balances;
    }

    /**
     * Get comparative balances for two periods.
     *
     * @return array<AccountBalanceDTO>
     */
    public function getComparativeBalances(string $tenantId, string $periodId, string $comparativePeriodId): array
    {
        // Get balances for both periods and combine
        $currentBalances = $this->getAccountBalances($tenantId, $periodId);
        // Comparative period logic would be implemented based on period dates
        return $currentBalances;
    }

    /**
     * Get period metadata.
     *
     * @return array<string, mixed>
     */
    public function getPeriodMetadata(string $tenantId, string $periodId): array
    {
        return [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Get cash flow data for a period.
     *
     * @return array<string, float>
     */
    public function getCashFlowData(string $tenantId, string $periodId): array
    {
        // Cash flow data would be derived from journal entries
        return [
            'operating_activities' => 0.0,
            'investing_activities' => 0.0,
            'financing_activities' => 0.0,
        ];
    }

    /**
     * Get equity movements for a period.
     *
     * @return array<string, mixed>
     */
    public function getEquityMovements(string $tenantId, string $periodId): array
    {
        // Equity movements would be derived from journal entries
        return [
            'beginning_balance' => 0.0,
            'net_income' => 0.0,
            'dividends' => 0.0,
            'other_adjustments' => 0.0,
            'ending_balance' => 0.0,
        ];
    }
}
