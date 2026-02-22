<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\DepreciationRun\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Calculate Depreciation.
 *
 * Forward action: Calculates depreciation amounts for all eligible assets
 * based on the specified depreciation method and fiscal period.
 * 
 * Compensation: Marks calculated depreciation as voided and removes
 * temporary calculation records.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class CalculateDepreciationStep implements WorkflowStepInterface
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
        return 'calculate_depreciation';
    }

    /**
     * Execute the forward action: Calculate depreciation for eligible assets.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting depreciation calculation', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $fiscalPeriodId = $context->get('fiscal_period_id');
            $depreciationMethod = $context->get('depreciation_method', 'straight_line');
            $runDate = $context->get('run_date');
            $assetIds = $context->get('asset_ids');
            $batchSize = $context->get('batch_size', 100);
            $dryRun = $context->get('dry_run', false);

            if ($fiscalPeriodId === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Fiscal period ID is required for depreciation calculation',
                );
            }

            if ($runDate === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Run date is required for depreciation calculation',
                );
            }

            // Simulate depreciation calculation
            // In production, this would call the FixedAssetDepreciation package via adapter
            $calculationId = sprintf('DEP-CALC-%s-%s', $fiscalPeriodId, bin2hex(random_bytes(8)));
            
            $calculationResult = [
                'calculation_id' => $calculationId,
                'fiscal_period_id' => $fiscalPeriodId,
                'depreciation_method' => $depreciationMethod,
                'run_date' => $runDate,
                'total_assets_processed' => $assetIds === null ? 150 : count($assetIds),
                'total_depreciation_amount' => 125000.00,
                'currency' => 'MYR',
                'calculation_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'dry_run' => $dryRun,
                'batch_size' => $batchSize,
                'asset_breakdown' => [
                    'buildings' => ['count' => 45, 'amount' => 50000.00],
                    'machinery' => ['count' => 60, 'amount' => 45000.00],
                    'vehicles' => ['count' => 25, 'amount' => 20000.00],
                    'equipment' => ['count' => 20, 'amount' => 10000.00],
                ],
            ];

            $this->getLogger()->info('Depreciation calculation completed', [
                'calculation_id' => $calculationId,
                'total_assets' => $calculationResult['total_assets_processed'],
                'total_amount' => $calculationResult['total_depreciation_amount'],
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $calculationResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Depreciation calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Depreciation calculation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Void the depreciation calculation.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Voiding depreciation calculation', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $calculationId = $previousResult['calculation_id'] ?? null;

            if ($calculationId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No depreciation calculation to void'],
                );
            }

            // In production, this would mark the calculation as voided
            $this->getLogger()->info('Depreciation calculation voided', [
                'calculation_id' => $calculationId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'voided_calculation_id' => $calculationId,
                    'reason' => 'Depreciation run workflow compensation',
                    'voided_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to void depreciation calculation during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to void depreciation calculation: ' . $e->getMessage(),
            );
        }
    }
}
