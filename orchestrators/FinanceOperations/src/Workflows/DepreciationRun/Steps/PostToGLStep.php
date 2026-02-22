<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\DepreciationRun\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Post to General Ledger.
 *
 * Forward action: Posts depreciation journal entries to the General Ledger
 * for all validated depreciation amounts.
 * 
 * Compensation: Creates reversing journal entries to undo the postings.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class PostToGLStep implements WorkflowStepInterface
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
        return 'post_to_gl';
    }

    /**
     * Execute the forward action: Post depreciation entries to GL.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting GL posting for depreciation', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the calculation and validation results from previous steps
            $calculationResult = $context->getPreviousStepResult('calculate_depreciation');
            $validationResult = $context->getPreviousStepResult('validate_depreciation');
            
            if ($calculationResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No depreciation calculation found to post',
                );
            }

            if ($validationResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No validation result found - cannot post unvalidated depreciation',
                );
            }

            $calculationId = $calculationResult['calculation_id'] ?? null;
            $totalDepreciation = $calculationResult['total_depreciation_amount'] ?? 0;
            $currency = $calculationResult['currency'] ?? 'MYR';
            $fiscalPeriodId = $calculationResult['fiscal_period_id'] ?? null;
            $dryRun = $context->get('dry_run', false);
            $postAutomatically = $context->get('post_automatically', true);

            if (!$postAutomatically) {
                $this->getLogger()->info('Automatic posting disabled - entries created but not posted', [
                    'calculation_id' => $calculationId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'journal_id' => sprintf('JE-DRAFT-%s', $calculationId),
                        'calculation_id' => $calculationId,
                        'status' => 'draft',
                        'message' => 'Journal entries created in draft mode',
                        'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            if ($dryRun) {
                $this->getLogger()->info('Dry run mode - simulating GL posting', [
                    'calculation_id' => $calculationId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'journal_id' => sprintf('JE-DRY-%s', $calculationId),
                        'calculation_id' => $calculationId,
                        'status' => 'simulated',
                        'message' => 'Dry run - no actual posting performed',
                        'simulated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            // Simulate GL posting
            // In production, this would call the Finance package via adapter
            $journalId = sprintf('JE-%s-%s', date('Ymd'), bin2hex(random_bytes(4)));
            
            // Create journal entries for each asset category
            $journalLines = [];
            $assetBreakdown = $calculationResult['asset_breakdown'] ?? [];
            $lineNumber = 1;

            foreach ($assetBreakdown as $category => $data) {
                // Debit: Depreciation Expense
                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => $this->getDepreciationExpenseAccount($category),
                    'account_name' => sprintf('Depreciation Expense - %s', ucfirst($category)),
                    'debit' => $data['amount'],
                    'credit' => 0,
                    'description' => sprintf('Depreciation for %s - Period %s', $category, $fiscalPeriodId),
                    'cost_center' => null,
                ];

                // Credit: Accumulated Depreciation
                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => $this->getAccumulatedDepreciationAccount($category),
                    'account_name' => sprintf('Accumulated Depreciation - %s', ucfirst($category)),
                    'debit' => 0,
                    'credit' => $data['amount'],
                    'description' => sprintf('Accumulated Depreciation for %s - Period %s', $category, $fiscalPeriodId),
                    'cost_center' => null,
                ];
            }

            $postingResult = [
                'journal_id' => $journalId,
                'calculation_id' => $calculationId,
                'validation_id' => $validationResult['validation_id'] ?? null,
                'status' => 'posted',
                'posted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'fiscal_period_id' => $fiscalPeriodId,
                'total_debit' => $totalDepreciation,
                'total_credit' => $totalDepreciation,
                'currency' => $currency,
                'line_count' => count($journalLines),
                'journal_lines' => $journalLines,
                'posting_reference' => sprintf('DEPRUN-%s', $fiscalPeriodId),
            ];

            $this->getLogger()->info('GL posting completed', [
                'journal_id' => $journalId,
                'total_debit' => $totalDepreciation,
                'line_count' => count($journalLines),
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $postingResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('GL posting failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'GL posting failed: ' . $e->getMessage(),
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
        $this->getLogger()->info('Compensating: Creating reversing journal entries', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $journalId = $previousResult['journal_id'] ?? null;

            if ($journalId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No journal entry to reverse'],
                );
            }

            // Skip reversal for draft or simulated entries
            $status = $previousResult['status'] ?? 'unknown';
            if (in_array($status, ['draft', 'simulated'], true)) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: [
                        'message' => 'No reversal needed for non-posted entry',
                        'original_status' => $status,
                    ],
                );
            }

            // In production, this would create a reversing journal entry
            $reversalJournalId = sprintf('JE-REV-%s', $journalId);
            
            $this->getLogger()->info('Reversing journal entry created', [
                'original_journal_id' => $journalId,
                'reversal_journal_id' => $reversalJournalId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'original_journal_id' => $journalId,
                    'reversal_journal_id' => $reversalJournalId,
                    'reason' => 'Depreciation run workflow compensation',
                    'reversed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to create reversing journal entry during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to reverse journal entry: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get the depreciation expense account code for an asset category.
     *
     * @param string $category Asset category
     * @return string Account code
     */
    private function getDepreciationExpenseAccount(string $category): string
    {
        return match ($category) {
            'buildings' => '6101',
            'machinery' => '6102',
            'vehicles' => '6103',
            'equipment' => '6104',
            default => '6100',
        };
    }

    /**
     * Get the accumulated depreciation account code for an asset category.
     *
     * @param string $category Asset category
     * @return string Account code
     */
    private function getAccumulatedDepreciationAccount(string $category): string
    {
        return match ($category) {
            'buildings' => '1601',
            'machinery' => '1602',
            'vehicles' => '1603',
            'equipment' => '1604',
            default => '1600',
        };
    }
}
