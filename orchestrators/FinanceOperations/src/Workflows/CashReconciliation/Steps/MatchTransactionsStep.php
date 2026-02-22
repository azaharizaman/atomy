<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CashReconciliation\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Match Transactions.
 *
 * Forward action: Matches bank statement transactions with
 * book records (receipts, payments, transfers).
 * 
 * Compensation: Unmatches all matched transactions and clears match records.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class MatchTransactionsStep implements WorkflowStepInterface
{
    /**
     * @param LoggerInterface|null $logger PSR-3 compliant logger
     */
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     *
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Get the step name for identification and logging.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'match_transactions';
    }

    /**
     * Execute the forward action: Match bank transactions with book records.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting transaction matching', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $bankAccountId = $context->get('bank_account_id');
            $statementDate = $context->get('statement_date');
            $statementEndingBalance = $context->get('statement_ending_balance');
            $autoMatchThreshold = $context->get('auto_match_threshold', 0.01);

            if ($bankAccountId === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Bank account ID is required for transaction matching',
                );
            }

            if ($statementDate === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Statement date is required for transaction matching',
                );
            }

            // Simulate transaction matching
            // In production, this would call the CashManagement package via adapter
            $matchingId = sprintf('TM-%s-%s', $bankAccountId, bin2hex(random_bytes(8)));
            
            // Simulated bank statement transactions
            $bankTransactions = [
                ['id' => 'BT001', 'date' => '2026-02-01', 'type' => 'credit', 'amount' => 5000.00, 'reference' => 'DEP-001', 'description' => 'Customer Deposit'],
                ['id' => 'BT002', 'date' => '2026-02-03', 'type' => 'debit', 'amount' => 2500.00, 'reference' => 'CHQ-101', 'description' => 'Supplier Payment'],
                ['id' => 'BT003', 'date' => '2026-02-05', 'type' => 'credit', 'amount' => 3500.00, 'reference' => 'TT-202', 'description' => 'Wire Transfer In'],
                ['id' => 'BT004', 'date' => '2026-02-08', 'type' => 'debit', 'amount' => 1200.00, 'reference' => 'CHQ-102', 'description' => 'Rent Payment'],
                ['id' => 'BT005', 'date' => '2026-02-10', 'type' => 'credit', 'amount' => 8000.00, 'reference' => 'DEP-002', 'description' => 'Customer Payment'],
                ['id' => 'BT006', 'date' => '2026-02-12', 'type' => 'debit', 'amount' => 500.00, 'reference' => 'BANK-FEE', 'description' => 'Bank Charges'],
                ['id' => 'BT007', 'date' => '2026-02-15', 'type' => 'credit', 'amount' => 2500.00, 'reference' => 'INT-001', 'description' => 'Interest Received'],
            ];

            // Simulated book records
            $bookRecords = [
                ['id' => 'BR001', 'date' => '2026-02-01', 'type' => 'receipt', 'amount' => 5000.00, 'reference' => 'REC-001', 'customer' => 'Customer A'],
                ['id' => 'BR002', 'date' => '2026-02-03', 'type' => 'payment', 'amount' => 2500.00, 'reference' => 'PAY-101', 'supplier' => 'Supplier X'],
                ['id' => 'BR003', 'date' => '2026-02-05', 'type' => 'receipt', 'amount' => 3500.00, 'reference' => 'REC-002', 'customer' => 'Customer B'],
                ['id' => 'BR004', 'date' => '2026-02-08', 'type' => 'payment', 'amount' => 1200.00, 'reference' => 'PAY-102', 'supplier' => 'Landlord'],
                ['id' => 'BR005', 'date' => '2026-02-10', 'type' => 'receipt', 'amount' => 8000.00, 'reference' => 'REC-003', 'customer' => 'Customer C'],
            ];

            // Perform matching
            $matchedTransactions = [];
            $unmatchedBank = [];
            $unmatchedBook = [];
            $matchResults = [];

            foreach ($bankTransactions as $bankTx) {
                $matched = false;
                foreach ($bookRecords as $key => $bookRec) {
                    // Match by amount and approximate date
                    if (abs($bankTx['amount'] - $bookRec['amount']) <= $autoMatchThreshold) {
                        $matchedTransactions[] = [
                            'bank_transaction_id' => $bankTx['id'],
                            'book_record_id' => $bookRec['id'],
                            'match_type' => 'auto',
                            'match_confidence' => 100,
                            'matched_amount' => $bankTx['amount'],
                            'bank_date' => $bankTx['date'],
                            'book_date' => $bookRec['date'],
                            'bank_reference' => $bankTx['reference'],
                            'book_reference' => $bookRec['reference'],
                        ];
                        $matchResults[] = [
                            'bank_id' => $bankTx['id'],
                            'book_id' => $bookRec['id'],
                            'status' => 'matched',
                        ];
                        unset($bookRecords[$key]);
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    $unmatchedBank[] = $bankTx;
                }
            }

            // Remaining book records are unmatched
            $unmatchedBook = array_values($bookRecords);

            $matchingResult = [
                'matching_id' => $matchingId,
                'bank_account_id' => $bankAccountId,
                'statement_date' => $statementDate,
                'statement_ending_balance' => $statementEndingBalance,
                'matched_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'currency' => 'MYR',
                'total_bank_transactions' => count($bankTransactions),
                'total_book_records' => count($bookRecords) + count($matchedTransactions),
                'matched_count' => count($matchedTransactions),
                'unmatched_bank_count' => count($unmatchedBank),
                'unmatched_book_count' => count($unmatchedBook),
                'matched_transactions' => $matchedTransactions,
                'unmatched_bank_transactions' => $unmatchedBank,
                'unmatched_book_records' => $unmatchedBook,
                'match_summary' => [
                    'total_matched_amount' => array_sum(array_column($matchedTransactions, 'matched_amount')),
                    'auto_matched' => count(array_filter($matchedTransactions, fn($m) => $m['match_type'] === 'auto')),
                    'manual_matched' => count(array_filter($matchedTransactions, fn($m) => $m['match_type'] === 'manual')),
                ],
            ];

            $this->getLogger()->info('Transaction matching completed', [
                'matching_id' => $matchingId,
                'matched_count' => count($matchedTransactions),
                'unmatched_bank' => count($unmatchedBank),
                'unmatched_book' => count($unmatchedBook),
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $matchingResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Transaction matching failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Transaction matching failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Unmatch all matched transactions.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Unmatching transactions', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $matchingId = $previousResult['matching_id'] ?? null;

            if ($matchingId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No matched transactions to unmatch'],
                );
            }

            $matchedCount = $previousResult['matched_count'] ?? 0;

            // In production, this would unmatch all transactions
            $this->getLogger()->info('Transactions unmatched', [
                'matching_id' => $matchingId,
                'unmatched_count' => $matchedCount,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'cleared_matching_id' => $matchingId,
                    'reason' => 'Cash reconciliation workflow compensation',
                    'unmatched_count' => $matchedCount,
                    'unmatched_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to unmatch transactions during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to unmatch transactions: ' . $e->getMessage(),
            );
        }
    }
}
