<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Data provider for treasury and cash flow data aggregation.
 *
 * Aggregates data from:
 * - Treasury package (bank accounts, cash positions)
 * - JournalEntry package (GL balances)
 * - Receivable package (incoming cash flows)
 * - Payable package (outgoing cash flows)
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 *
 * @since 1.0.0
 */
final readonly class TreasuryDataProvider implements TreasuryDataProviderInterface
{
    public function __construct(
        private object $treasuryManager,  // TreasuryManagerInterface from atomic package
        private object $journalEntryQuery,  // JournalEntryQueryInterface from atomic package
        private ?object $receivableQuery = null,  // ReceivableQueryInterface
        private ?object $payableQuery = null,  // PayableQueryInterface
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getCashPosition(string $tenantId, string $bankAccountId): array
    {
        $this->logger->debug('Fetching cash position', [
            'tenant_id' => $tenantId,
            'bank_account_id' => $bankAccountId,
        ]);

        try {
            // Get position from Treasury package
            $position = $this->treasuryManager->getPosition($tenantId, $bankAccountId);

            return [
                'bank_account_id' => $bankAccountId,
                'balance' => $position->getBalance(),
                'currency' => $position->getCurrency(),
                'as_of_date' => $position->getAsOfDate()->format('Y-m-d'),
                'available_balance' => $position->getAvailableBalance(),
                'pending_credits' => $position->getPendingCredits(),
                'pending_debits' => $position->getPendingDebits(),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch cash position', [
                'tenant_id' => $tenantId,
                'bank_account_id' => $bankAccountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCashFlowForecast(string $tenantId, string $periodId): array
    {
        $this->logger->debug('Generating cash flow forecast', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        $inflows = [];
        $outflows = [];

        // Aggregate expected inflows from Receivables
        if ($this->receivableQuery !== null) {
            try {
                $expectedReceipts = $this->receivableQuery->getExpectedReceipts($tenantId, $periodId);
                foreach ($expectedReceipts as $receipt) {
                    $inflows[] = [
                        'date' => $receipt->getExpectedDate()->format('Y-m-d'),
                        'amount' => $receipt->getAmount(),
                        'currency' => $receipt->getCurrency(),
                        'source' => 'receivable',
                        'reference' => $receipt->getReference(),
                        'party_id' => $receipt->getPartyId(),
                    ];
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch expected receipts', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Aggregate expected outflows from Payables
        if ($this->payableQuery !== null) {
            try {
                $expectedPayments = $this->payableQuery->getExpectedPayments($tenantId, $periodId);
                foreach ($expectedPayments as $payment) {
                    $outflows[] = [
                        'date' => $payment->getDueDate()->format('Y-m-d'),
                        'amount' => $payment->getAmount(),
                        'currency' => $payment->getCurrency(),
                        'source' => 'payable',
                        'reference' => $payment->getReference(),
                        'party_id' => $payment->getPartyId(),
                    ];
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch expected payments', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'period_id' => $periodId,
            'inflows' => $inflows,
            'outflows' => $outflows,
            'total_inflows' => $this->calculateTotal($inflows),
            'total_outflows' => $this->calculateTotal($outflows),
            'net_cash_flow' => $this->calculateNetCashFlow($inflows, $outflows),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getBankReconciliationData(string $tenantId, string $bankAccountId): array
    {
        $this->logger->debug('Fetching bank reconciliation data', [
            'tenant_id' => $tenantId,
            'bank_account_id' => $bankAccountId,
        ]);

        try {
            // Get bank statement lines from Treasury
            $statementLines = $this->treasuryManager->getStatementLines($tenantId, $bankAccountId);

            // Get GL transactions for the bank account
            $glTransactions = $this->journalEntryQuery->getAccountTransactions($tenantId, $bankAccountId);

            return [
                'bank_account_id' => $bankAccountId,
                'statement_lines' => array_map(fn($line) => [
                    'date' => $line->getDate()->format('Y-m-d'),
                    'description' => $line->getDescription(),
                    'amount' => $line->getAmount(),
                    'reference' => $line->getReference(),
                    'is_reconciled' => $line->isReconciled(),
                ], $statementLines),
                'gl_transactions' => array_map(fn($txn) => [
                    'date' => $txn->getDate()->format('Y-m-d'),
                    'description' => $txn->getDescription(),
                    'amount' => $txn->getAmount(),
                    'reference' => $txn->getReference(),
                    'is_reconciled' => $txn->isReconciled(),
                ], $glTransactions),
                'statement_balance' => $this->calculateStatementBalance($statementLines),
                'gl_balance' => $this->calculateGLBalance($glTransactions),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch bank reconciliation data', [
                'tenant_id' => $tenantId,
                'bank_account_id' => $bankAccountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getBankAccounts(string $tenantId): array
    {
        $this->logger->debug('Fetching bank accounts', ['tenant_id' => $tenantId]);

        try {
            $accounts = $this->treasuryManager->getBankAccounts($tenantId);
            $result = [];

            foreach ($accounts as $account) {
                $result[] = [
                    'id' => $account->getId(),
                    'name' => $account->getName(),
                    'currency' => $account->getCurrency(),
                    'bank_name' => $account->getBankName(),
                    'account_number' => $account->getAccountNumber(),
                    'is_active' => $account->isActive(),
                    'gl_account_code' => $account->getGLAccountCode(),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch bank accounts', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate total from flow items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private function calculateTotal(array $items): string
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) ($item['amount'] ?? 0);
        }
        return (string) $total;
    }

    /**
     * Calculate net cash flow.
     *
     * @param array<int, array<string, mixed>> $inflows
     * @param array<int, array<string, mixed>> $outflows
     */
    private function calculateNetCashFlow(array $inflows, array $outflows): string
    {
        $net = (float) $this->calculateTotal($inflows) - (float) $this->calculateTotal($outflows);
        return (string) $net;
    }

    /**
     * Calculate statement balance from lines.
     *
     * @param array<int, object> $lines
     */
    private function calculateStatementBalance(array $lines): string
    {
        $balance = 0.0;
        foreach ($lines as $line) {
            $balance += (float) $line->getAmount();
        }
        return (string) $balance;
    }

    /**
     * Calculate GL balance from transactions.
     *
     * @param array<int, object> $transactions
     */
    private function calculateGLBalance(array $transactions): string
    {
        $balance = 0.0;
        foreach ($transactions as $txn) {
            $balance += (float) $txn->getAmount();
        }
        return (string) $balance;
    }
}
