<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CashReconciliation\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Create Adjusting Entries.
 *
 * Forward action: Creates journal entries to adjust the book balance
 * for identified discrepancies (bank charges, interest, errors).
 * 
 * Compensation: Creates reversing journal entries to undo adjustments.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class CreateAdjustingEntriesStep implements WorkflowStepInterface
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
        return 'create_adjusting_entries';
    }

    /**
     * Execute the forward action: Create adjusting journal entries.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting adjusting entries creation', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the discrepancy analysis from previous step
            $discrepancyResult = $context->getPreviousStepResult('identify_discrepancies');
            $matchingResult = $context->getPreviousStepResult('match_transactions');
            
            if ($discrepancyResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No discrepancy analysis found',
                );
            }

            $analysisId = $discrepancyResult['analysis_id'] ?? null;
            $requiresAdjustment = $discrepancyResult['requires_adjustment'] ?? false;
            $createAutomatically = $context->get('create_adjustments_automatically', true);
            $adjustmentAccount = $context->get('adjustment_account', '7900');
            $dryRun = $context->get('dry_run', false);

            if (!$requiresAdjustment) {
                $this->getLogger()->info('No adjustments required', [
                    'analysis_id' => $analysisId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'adjustment_id' => sprintf('ADJ-NONE-%s', $analysisId),
                        'analysis_id' => $analysisId,
                        'status' => 'no_adjustment_needed',
                        'message' => 'No discrepancies requiring adjustment',
                        'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            if (!$createAutomatically) {
                $this->getLogger()->info('Automatic adjustment creation disabled', [
                    'analysis_id' => $analysisId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'adjustment_id' => sprintf('ADJ-PENDING-%s', $analysisId),
                        'analysis_id' => $analysisId,
                        'status' => 'pending_approval',
                        'message' => 'Adjustments require manual approval',
                        'adjustment_amount' => $discrepancyResult['adjustment_amount'] ?? 0,
                        'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            if ($dryRun) {
                $this->getLogger()->info('Dry run mode - simulating adjustments', [
                    'analysis_id' => $analysisId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'adjustment_id' => sprintf('ADJ-DRY-%s', $analysisId),
                        'analysis_id' => $analysisId,
                        'status' => 'simulated',
                        'message' => 'Dry run - no actual adjustments created',
                        'simulated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            // Simulate journal entry creation
            // In production, this would call the Finance package via adapter
            $journalId = sprintf('JE-RECON-%s-%s', date('Ymd'), bin2hex(random_bytes(4)));
            $adjustmentId = sprintf('ADJ-%s', $analysisId);
            
            $discrepancies = $discrepancyResult['discrepancies'] ?? [];
            $currency = $discrepancyResult['currency'] ?? 'MYR';
            $statementDate = $discrepancyResult['statement_date'] ?? date('Y-m-d');
            $bankAccountId = $matchingResult['bank_account_id'] ?? null;

            // Create journal lines for each type of discrepancy
            $journalLines = [];
            $lineNumber = 1;
            $totalDebits = 0;
            $totalCredits = 0;

            // Bank charges - debit expense, credit bank
            $bankCharges = $discrepancies['bank_charges'] ?? [];
            if (!empty($bankCharges)) {
                $totalBankCharges = array_sum(array_column($bankCharges, 'amount'));
                
                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => '7901', // Bank Charges Expense
                    'account_name' => 'Bank Charges Expense',
                    'debit' => $totalBankCharges,
                    'credit' => 0,
                    'description' => 'Bank charges for period',
                    'reference' => $adjustmentId,
                ];
                $totalDebits += $totalBankCharges;

                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => $this->getBankAccountCode($bankAccountId),
                    'account_name' => sprintf('Bank Account - %s', $bankAccountId),
                    'debit' => 0,
                    'credit' => $totalBankCharges,
                    'description' => 'Bank charges per statement',
                    'reference' => $adjustmentId,
                ];
                $totalCredits += $totalBankCharges;
            }

            // Interest earned - debit bank, credit income
            $interestEarned = $discrepancies['interest_earned'] ?? [];
            if (!empty($interestEarned)) {
                $totalInterest = array_sum(array_column($interestEarned, 'amount'));
                
                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => $this->getBankAccountCode($bankAccountId),
                    'account_name' => sprintf('Bank Account - %s', $bankAccountId),
                    'debit' => $totalInterest,
                    'credit' => 0,
                    'description' => 'Interest earned per statement',
                    'reference' => $adjustmentId,
                ];
                $totalDebits += $totalInterest;

                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => '4900', // Interest Income
                    'account_name' => 'Interest Income',
                    'debit' => 0,
                    'credit' => $totalInterest,
                    'description' => 'Interest income for period',
                    'reference' => $adjustmentId,
                ];
                $totalCredits += $totalInterest;
            }

            // Other items
            $otherItems = $discrepancies['other'] ?? [];
            if (!empty($otherItems)) {
                $totalOther = array_sum(array_column($otherItems, 'amount'));
                $netOther = 0;

                foreach ($otherItems as $item) {
                    if ($item['type'] === 'credit') {
                        $netOther += $item['amount'];
                    } else {
                        $netOther -= $item['amount'];
                    }
                }

                if ($netOther !== 0) {
                    if ($netOther > 0) {
                        // Net credit - debit bank, credit suspense
                        $journalLines[] = [
                            'line_number' => $lineNumber++,
                            'account_code' => $this->getBankAccountCode($bankAccountId),
                            'account_name' => sprintf('Bank Account - %s', $bankAccountId),
                            'debit' => abs($netOther),
                            'credit' => 0,
                            'description' => 'Other reconciling items',
                            'reference' => $adjustmentId,
                        ];
                        $totalDebits += abs($netOther);

                        $journalLines[] = [
                            'line_number' => $lineNumber++,
                            'account_code' => $adjustmentAccount,
                            'account_name' => 'Reconciliation Suspense',
                            'debit' => 0,
                            'credit' => abs($netOther),
                            'description' => 'Other reconciling items - pending investigation',
                            'reference' => $adjustmentId,
                        ];
                        $totalCredits += abs($netOther);
                    } else {
                        // Net debit - credit bank, debit suspense
                        $journalLines[] = [
                            'line_number' => $lineNumber++,
                            'account_code' => $adjustmentAccount,
                            'account_name' => 'Reconciliation Suspense',
                            'debit' => abs($netOther),
                            'credit' => 0,
                            'description' => 'Other reconciling items - pending investigation',
                            'reference' => $adjustmentId,
                        ];
                        $totalDebits += abs($netOther);

                        $journalLines[] = [
                            'line_number' => $lineNumber++,
                            'account_code' => $this->getBankAccountCode($bankAccountId),
                            'account_name' => sprintf('Bank Account - %s', $bankAccountId),
                            'debit' => 0,
                            'credit' => abs($netOther),
                            'description' => 'Other reconciling items',
                            'reference' => $adjustmentId,
                        ];
                        $totalCredits += abs($netOther);
                    }
                }
            }

            $adjustmentResult = [
                'adjustment_id' => $adjustmentId,
                'analysis_id' => $analysisId,
                'matching_id' => $matchingResult['matching_id'] ?? null,
                'journal_id' => $journalId,
                'status' => 'posted',
                'posted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'statement_date' => $statementDate,
                'bank_account_id' => $bankAccountId,
                'currency' => $currency,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'line_count' => count($journalLines),
                'journal_lines' => $journalLines,
                'adjustments_summary' => [
                    'bank_charges' => array_sum(array_column($bankCharges, 'amount')),
                    'interest_earned' => array_sum(array_column($interestEarned, 'amount')),
                    'other_items' => array_sum(array_column($otherItems, 'amount')),
                ],
                'reconciliation_status' => 'reconciled',
            ];

            $this->getLogger()->info('Adjusting entries created', [
                'adjustment_id' => $adjustmentId,
                'journal_id' => $journalId,
                'total_adjustments' => $totalDebits,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $adjustmentResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Adjusting entries creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Adjusting entries creation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Create reversing journal entries.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Creating reversing entries', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $adjustmentId = $previousResult['adjustment_id'] ?? null;
            $journalId = $previousResult['journal_id'] ?? null;

            if ($adjustmentId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No adjusting entries to reverse'],
                );
            }

            // Skip reversal for non-posted entries
            $status = $previousResult['status'] ?? 'unknown';
            if (in_array($status, ['no_adjustment_needed', 'pending_approval', 'simulated'], true)) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: [
                        'message' => 'No reversal needed for non-posted adjustment',
                        'original_status' => $status,
                    ],
                );
            }

            // In production, this would create a reversing journal entry
            $reversalJournalId = sprintf('JE-REV-%s', $journalId);
            
            $this->getLogger()->info('Reversing journal entry created', [
                'original_adjustment_id' => $adjustmentId,
                'original_journal_id' => $journalId,
                'reversal_journal_id' => $reversalJournalId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'original_adjustment_id' => $adjustmentId,
                    'original_journal_id' => $journalId,
                    'reversal_journal_id' => $reversalJournalId,
                    'reason' => 'Cash reconciliation workflow compensation',
                    'reversed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to create reversing entries during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to reverse adjusting entries: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get the GL account code for a bank account.
     *
     * @param string|null $bankAccountId Bank account ID
     * @return string GL account code
     */
    private function getBankAccountCode(?string $bankAccountId): string
    {
        // In production, this would look up the GL account for the bank
        return match ($bankAccountId) {
            'BANK001' => '1101',
            'BANK002' => '1102',
            default => '1100',
        };
    }
}
