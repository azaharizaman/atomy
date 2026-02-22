<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\CostAllocation\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Post Allocated Costs.
 *
 * Forward action: Posts allocated costs to the General Ledger
 * for each cost center based on allocation results.
 * 
 * Compensation: Creates reversing journal entries to undo the postings.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class PostAllocatedCostsStep implements WorkflowStepInterface
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
        return 'post_allocated_costs';
    }

    /**
     * Execute the forward action: Post allocated costs to GL.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting allocated costs posting', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the allocation results from previous step
            $allocationResult = $context->getPreviousStepResult('apply_allocation_rules');
            $gatheredCosts = $context->getPreviousStepResult('gather_costs');
            
            if ($allocationResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No allocation results found to post',
                );
            }

            $allocationId = $allocationResult['allocation_id'] ?? null;
            $gatheringId = $allocationResult['gathering_id'] ?? null;
            $fiscalPeriodId = $gatheredCosts['fiscal_period_id'] ?? null;
            $currency = $allocationResult['currency'] ?? 'MYR';
            $dryRun = $context->get('dry_run', false);
            $postAutomatically = $context->get('post_automatically', true);

            if (!$postAutomatically) {
                $this->getLogger()->info('Automatic posting disabled - entries created but not posted', [
                    'allocation_id' => $allocationId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'journal_id' => sprintf('JE-DRAFT-%s', $allocationId),
                        'allocation_id' => $allocationId,
                        'status' => 'draft',
                        'message' => 'Journal entries created in draft mode',
                        'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            if ($dryRun) {
                $this->getLogger()->info('Dry run mode - simulating posting', [
                    'allocation_id' => $allocationId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'journal_id' => sprintf('JE-DRY-%s', $allocationId),
                        'allocation_id' => $allocationId,
                        'status' => 'simulated',
                        'message' => 'Dry run - no actual posting performed',
                        'simulated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            // Create journal entries for the allocated costs
            // In production, this would call the Finance package via adapter
            // The adapter would handle the actual GL posting to the general ledger
            $journalId = sprintf('JE-ALLOC-%s-%s', date('Ymd'), bin2hex(random_bytes(4)));
            
            // Create journal entries for each allocation
            $journalLines = [];
            $lineNumber = 1;
            $allocations = $allocationResult['allocations'] ?? [];
            $costCenterTotals = $allocationResult['cost_center_totals'] ?? [];

            // Credit: Clearing accounts for each cost source
            foreach ($allocations as $sourceName => $sourceData) {
                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => $this->getClearingAccount($sourceName),
                    'account_name' => sprintf('Cost Clearing - %s', ucfirst(str_replace('_', ' ', $sourceName))),
                    'debit' => 0,
                    'credit' => $sourceData['total_allocated'],
                    'description' => sprintf('Cost allocation clearing - %s', $sourceName),
                    'cost_center' => null,
                ];
            }

            // Debit: Cost center expense accounts
            foreach ($costCenterTotals as $costCenter => $amount) {
                $journalLines[] = [
                    'line_number' => $lineNumber++,
                    'account_code' => $this->getCostCenterAccount($costCenter),
                    'account_name' => sprintf('Allocated Costs - %s', $costCenter),
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => sprintf('Allocated costs for %s', $costCenter),
                    'cost_center' => $costCenter,
                ];
            }

            $postingResult = [
                'journal_id' => $journalId,
                'allocation_id' => $allocationId,
                'gathering_id' => $gatheringId,
                'status' => 'posted',
                'posted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'fiscal_period_id' => $fiscalPeriodId,
                'total_debit' => array_sum($costCenterTotals),
                'total_credit' => array_sum(array_column($allocations, 'total_allocated')),
                'currency' => $currency,
                'line_count' => count($journalLines),
                'journal_lines' => $journalLines,
                'posting_reference' => sprintf('COSTALLOC-%s', $fiscalPeriodId),
                'cost_centers_posted' => array_keys($costCenterTotals),
            ];

            $this->getLogger()->info('Allocated costs posted', [
                'journal_id' => $journalId,
                'total_debit' => $postingResult['total_debit'],
                'cost_centers' => count($costCenterTotals),
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $postingResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Allocated costs posting failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Allocated costs posting failed: ' . $e->getMessage(),
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
                    'reason' => 'Cost allocation workflow compensation',
                    'reversed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    'cost_centers_reversed' => $previousResult['cost_centers_posted'] ?? [],
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
     * Get the clearing account code for a cost source.
     *
     * @param string $sourceName Cost source name
     * @return string Account code
     */
    private function getClearingAccount(string $sourceName): string
    {
        return match ($sourceName) {
            'direct_labor' => '9101',
            'direct_materials' => '9102',
            'overhead' => '9103',
            'utilities' => '9104',
            default => '9100',
        };
    }

    /**
     * Get the cost center expense account code.
     *
     * @param string $costCenter Cost center code
     * @return string Account code
     */
    private function getCostCenterAccount(string $costCenter): string
    {
        // Map cost centers to expense accounts
        return match ($costCenter) {
            'CC001' => '7201',
            'CC002' => '7202',
            'CC003' => '7203',
            default => '7200',
        };
    }
}
