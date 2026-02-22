<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\DepreciationRun\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Update Asset Register.
 *
 * Forward action: Updates the asset register with new book values
 * after depreciation has been posted to the General Ledger.
 * 
 * Compensation: Restores previous book values and marks the update as reversed.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class UpdateAssetRegisterStep implements WorkflowStepInterface
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
        return 'update_asset_register';
    }

    /**
     * Execute the forward action: Update asset register with new book values.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting asset register update', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the calculation and GL posting results from previous steps
            $calculationResult = $context->getPreviousStepResult('calculate_depreciation');
            $glPostingResult = $context->getPreviousStepResult('post_to_gl');
            
            if ($calculationResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No depreciation calculation found',
                );
            }

            if ($glPostingResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No GL posting found - cannot update asset register',
                );
            }

            $calculationId = $calculationResult['calculation_id'] ?? null;
            $journalId = $glPostingResult['journal_id'] ?? null;
            $fiscalPeriodId = $calculationResult['fiscal_period_id'] ?? null;
            $dryRun = $context->get('dry_run', false);

            // Skip actual update for draft or simulated postings
            $postingStatus = $glPostingResult['status'] ?? 'unknown';
            if (in_array($postingStatus, ['draft', 'simulated'], true)) {
                $this->getLogger()->info('Asset register update skipped - posting not finalized', [
                    'calculation_id' => $calculationId,
                    'posting_status' => $postingStatus,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'update_id' => sprintf('AR-SKIP-%s', $calculationId),
                        'calculation_id' => $calculationId,
                        'status' => 'skipped',
                        'message' => 'Asset register update skipped - posting not finalized',
                        'skipped_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            if ($dryRun) {
                $this->getLogger()->info('Dry run mode - simulating asset register update', [
                    'calculation_id' => $calculationId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'update_id' => sprintf('AR-DRY-%s', $calculationId),
                        'calculation_id' => $calculationId,
                        'status' => 'simulated',
                        'message' => 'Dry run - no actual update performed',
                        'simulated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                );
            }

            // Simulate asset register update
            // In production, this would call the Assets package via adapter
            $updateId = sprintf('AR-%s-%s', $fiscalPeriodId, bin2hex(random_bytes(4)));
            
            $assetBreakdown = $calculationResult['asset_breakdown'] ?? [];
            $updatedAssets = [];

            foreach ($assetBreakdown as $category => $data) {
                $updatedAssets[$category] = [
                    'assets_updated' => $data['count'],
                    'total_depreciation' => $data['amount'],
                    'previous_book_value' => $data['amount'] * 10, // Simulated
                    'new_book_value' => $data['amount'] * 9, // Simulated
                    'last_depreciation_date' => $calculationResult['run_date'],
                ];
            }

            $updateResult = [
                'update_id' => $updateId,
                'calculation_id' => $calculationId,
                'journal_id' => $journalId,
                'fiscal_period_id' => $fiscalPeriodId,
                'status' => 'completed',
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'total_assets_updated' => $calculationResult['total_assets_processed'],
                'total_depreciation_recorded' => $calculationResult['total_depreciation_amount'],
                'currency' => $calculationResult['currency'] ?? 'MYR',
                'updated_categories' => $updatedAssets,
                'audit_trail' => [
                    'workflow_id' => $context->workflowId,
                    'depreciation_method' => $calculationResult['depreciation_method'],
                    'run_date' => $calculationResult['run_date'],
                ],
            ];

            $this->getLogger()->info('Asset register update completed', [
                'update_id' => $updateId,
                'total_assets' => $updateResult['total_assets_updated'],
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $updateResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Asset register update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Asset register update failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Restore previous book values.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Restoring asset register values', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $updateId = $previousResult['update_id'] ?? null;

            if ($updateId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No asset register update to reverse'],
                );
            }

            // Skip reversal for skipped or simulated updates
            $status = $previousResult['status'] ?? 'unknown';
            if (in_array($status, ['skipped', 'simulated'], true)) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: [
                        'message' => 'No reversal needed for non-applied update',
                        'original_status' => $status,
                    ],
                );
            }

            // In production, this would restore previous book values
            $reversalId = sprintf('AR-REV-%s', $updateId);
            
            $this->getLogger()->info('Asset register values restored', [
                'original_update_id' => $updateId,
                'reversal_id' => $reversalId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'original_update_id' => $updateId,
                    'reversal_id' => $reversalId,
                    'reason' => 'Depreciation run workflow compensation',
                    'restored_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    'assets_restored' => $previousResult['total_assets_updated'] ?? 0,
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to restore asset register values during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to restore asset register values: ' . $e->getMessage(),
            );
        }
    }
}
