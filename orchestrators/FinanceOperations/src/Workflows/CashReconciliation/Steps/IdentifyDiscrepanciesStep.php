<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CashReconciliation\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Identify Discrepancies.
 *
 * Forward action: Analyzes unmatched transactions and calculates
 * discrepancies between bank statement and book balances.
 * 
 * Compensation: Clears discrepancy analysis records.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class IdentifyDiscrepanciesStep implements WorkflowStepInterface
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
        return 'identify_discrepancies';
    }

    /**
     * Execute the forward action: Identify discrepancies between bank and book.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting discrepancy identification', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the matching results from previous step
            $matchingResult = $context->getPreviousStepResult('match_transactions');
            
            if ($matchingResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No matching results found to analyze',
                );
            }

            $matchingId = $matchingResult['matching_id'] ?? null;
            $statementEndingBalance = $matchingResult['statement_ending_balance'] ?? 0;
            $unmatchedBank = $matchingResult['unmatched_bank_transactions'] ?? [];
            $unmatchedBook = $matchingResult['unmatched_book_records'] ?? [];
            $matchedAmount = $matchingResult['match_summary']['total_matched_amount'] ?? 0;

            // Simulate book balance calculation
            // In production, this would call the Finance package via adapter
            $bookBalance = $statementEndingBalance; // Starting point
            
            // Calculate adjustments from unmatched items
            $discrepancies = [
                'deposits_in_transit' => [],
                'outstanding_checks' => [],
                'bank_charges' => [],
                'interest_earned' => [],
                'errors' => [],
                'other' => [],
            ];

            $totalDepositsInTransit = 0;
            $totalOutstandingChecks = 0;
            $totalBankCharges = 0;
            $totalInterestEarned = 0;
            $totalOther = 0;

            // Analyze unmatched bank transactions
            foreach ($unmatchedBank as $bankTx) {
                $discrepancy = [
                    'id' => $bankTx['id'],
                    'date' => $bankTx['date'],
                    'amount' => $bankTx['amount'],
                    'type' => $bankTx['type'],
                    'reference' => $bankTx['reference'],
                    'description' => $bankTx['description'],
                    'suggested_type' => $this->suggestDiscrepancyType($bankTx),
                ];

                if ($bankTx['type'] === 'credit') {
                    if (str_contains(strtolower($bankTx['description']), 'interest')) {
                        $discrepancies['interest_earned'][] = $discrepancy;
                        $totalInterestEarned += $bankTx['amount'];
                    } else {
                        $discrepancies['other'][] = $discrepancy;
                        $totalOther += $bankTx['amount'];
                    }
                } else {
                    if (str_contains(strtolower($bankTx['reference']), 'fee') || 
                        str_contains(strtolower($bankTx['description']), 'charge')) {
                        $discrepancies['bank_charges'][] = $discrepancy;
                        $totalBankCharges += $bankTx['amount'];
                    } else {
                        $discrepancies['other'][] = $discrepancy;
                        $totalOther += $bankTx['amount'];
                    }
                }
            }

            // Analyze unmatched book records
            foreach ($unmatchedBook as $bookRec) {
                $discrepancy = [
                    'id' => $bookRec['id'],
                    'date' => $bookRec['date'],
                    'amount' => $bookRec['amount'],
                    'type' => $bookRec['type'],
                    'reference' => $bookRec['reference'],
                    'suggested_type' => $bookRec['type'] === 'receipt' ? 'deposit_in_transit' : 'outstanding_check',
                ];

                if ($bookRec['type'] === 'receipt') {
                    $discrepancies['deposits_in_transit'][] = $discrepancy;
                    $totalDepositsInTransit += $bookRec['amount'];
                } else {
                    $discrepancies['outstanding_checks'][] = $discrepancy;
                    $totalOutstandingChecks += $bookRec['amount'];
                }
            }

            // Calculate reconciled balance
            $reconciledBalance = $statementEndingBalance 
                + $totalDepositsInTransit 
                - $totalOutstandingChecks 
                - $totalBankCharges 
                + $totalInterestEarned;

            $variance = abs($bookBalance - $reconciledBalance);

            $analysisId = sprintf('DA-%s', $matchingId);
            
            $discrepancyResult = [
                'analysis_id' => $analysisId,
                'matching_id' => $matchingId,
                'bank_account_id' => $matchingResult['bank_account_id'],
                'statement_date' => $matchingResult['statement_date'],
                'analyzed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'currency' => $matchingResult['currency'] ?? 'MYR',
                'balances' => [
                    'statement_ending_balance' => $statementEndingBalance,
                    'book_balance' => $bookBalance,
                    'reconciled_balance' => $reconciledBalance,
                    'variance' => $variance,
                ],
                'discrepancies' => $discrepancies,
                'summary' => [
                    'deposits_in_transit' => [
                        'count' => count($discrepancies['deposits_in_transit']),
                        'total' => $totalDepositsInTransit,
                    ],
                    'outstanding_checks' => [
                        'count' => count($discrepancies['outstanding_checks']),
                        'total' => $totalOutstandingChecks,
                    ],
                    'bank_charges' => [
                        'count' => count($discrepancies['bank_charges']),
                        'total' => $totalBankCharges,
                    ],
                    'interest_earned' => [
                        'count' => count($discrepancies['interest_earned']),
                        'total' => $totalInterestEarned,
                    ],
                    'other' => [
                        'count' => count($discrepancies['other']),
                        'total' => $totalOther,
                    ],
                ],
                'requires_adjustment' => $variance > 0 || $totalBankCharges > 0 || $totalInterestEarned > 0,
                'adjustment_amount' => $totalBankCharges + $totalInterestEarned + $totalOther,
            ];

            $this->getLogger()->info('Discrepancy identification completed', [
                'analysis_id' => $analysisId,
                'variance' => $variance,
                'requires_adjustment' => $discrepancyResult['requires_adjustment'],
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $discrepancyResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Discrepancy identification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Discrepancy identification failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Clear discrepancy analysis.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Clearing discrepancy analysis', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $analysisId = $previousResult['analysis_id'] ?? null;

            if ($analysisId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No discrepancy analysis to clear'],
                );
            }

            // In production, this would clear analysis records
            $this->getLogger()->info('Discrepancy analysis cleared', [
                'analysis_id' => $analysisId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'cleared_analysis_id' => $analysisId,
                    'reason' => 'Cash reconciliation workflow compensation',
                    'cleared_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear discrepancy analysis during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to clear discrepancy analysis: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Suggest the type of discrepancy based on transaction details.
     *
     * @param array<string, mixed> $transaction Transaction data
     * @return string Suggested discrepancy type
     */
    private function suggestDiscrepancyType(array $transaction): string
    {
        $description = strtolower($transaction['description'] ?? '');
        $reference = strtolower($transaction['reference'] ?? '');

        if (str_contains($description, 'interest') || str_contains($reference, 'int')) {
            return 'interest_earned';
        }

        if (str_contains($description, 'charge') || str_contains($description, 'fee') || 
            str_contains($reference, 'fee')) {
            return 'bank_charges';
        }

        return 'other';
    }
}
