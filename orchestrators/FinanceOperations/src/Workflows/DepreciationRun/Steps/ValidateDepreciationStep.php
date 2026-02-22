<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Workflows\DepreciationRun\Steps;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow step: Validate Depreciation.
 *
 * Forward action: Validates calculated depreciation amounts against
 * business rules, thresholds, and compliance requirements.
 * 
 * Compensation: Clears validation flags and audit trail entries.
 *
 * @see WorkflowStepInterface
 * @since 1.0.0
 */
final readonly class ValidateDepreciationStep implements WorkflowStepInterface
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
        return 'validate_depreciation';
    }

    /**
     * Execute the forward action: Validate depreciation calculations.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The step result
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Starting depreciation validation', [
            'workflow_id' => $context->workflowId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            // Get the calculation result from previous step
            $calculationResult = $context->getPreviousStepResult('calculate_depreciation');
            
            if ($calculationResult === null) {
                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'No depreciation calculation found to validate',
                );
            }

            $calculationId = $calculationResult['calculation_id'] ?? null;
            $totalDepreciation = $calculationResult['total_depreciation_amount'] ?? 0;
            $skipValidation = $context->get('skip_validation', false);

            if ($skipValidation) {
                $this->getLogger()->info('Validation skipped by request', [
                    'calculation_id' => $calculationId,
                ]);

                return WorkflowStepResult::success(
                    stepName: $this->getName(),
                    data: [
                        'validation_id' => sprintf('VAL-SKIP-%s', $calculationId),
                        'calculation_id' => $calculationId,
                        'status' => 'skipped',
                        'skipped_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'warnings' => [],
                        'errors' => [],
                    ],
                );
            }

            // Simulate validation process
            // In production, this would call validation rules from the Rules directory
            $validationId = sprintf('VAL-%s', $calculationId);
            
            $validationRules = [
                'book_value_not_negative' => true,
                'depreciation_within_limits' => true,
                'asset_life_remaining' => true,
                'period_not_closed' => true,
                'no_duplicate_run' => true,
            ];

            $warnings = [];
            $errors = [];

            // Check for high-value depreciation warnings
            if ($totalDepreciation > 100000) {
                $warnings[] = [
                    'code' => 'HIGH_VALUE_DEPRECIATION',
                    'message' => 'Total depreciation exceeds threshold of 100,000',
                    'threshold' => 100000,
                    'actual' => $totalDepreciation,
                ];
            }

            // Check asset breakdown for anomalies
            $assetBreakdown = $calculationResult['asset_breakdown'] ?? [];
            foreach ($assetBreakdown as $category => $data) {
                if ($data['amount'] < 0) {
                    $errors[] = [
                        'code' => 'NEGATIVE_DEPRECIATION',
                        'message' => sprintf('Negative depreciation detected for category: %s', $category),
                        'category' => $category,
                        'amount' => $data['amount'],
                    ];
                }
            }

            $validationResult = [
                'validation_id' => $validationId,
                'calculation_id' => $calculationId,
                'status' => empty($errors) ? 'passed' : 'failed',
                'validated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'rules_checked' => $validationRules,
                'warnings' => $warnings,
                'errors' => $errors,
                'summary' => [
                    'total_rules' => count($validationRules),
                    'passed_rules' => count(array_filter($validationRules)),
                    'warning_count' => count($warnings),
                    'error_count' => count($errors),
                ],
            ];

            if (!empty($errors)) {
                $this->getLogger()->error('Depreciation validation failed', [
                    'validation_id' => $validationId,
                    'errors' => $errors,
                ]);

                return WorkflowStepResult::failure(
                    stepName: $this->getName(),
                    error: 'Validation failed with ' . count($errors) . ' error(s)',
                );
            }

            $this->getLogger()->info('Depreciation validation completed', [
                'validation_id' => $validationId,
                'status' => $validationResult['status'],
                'warnings' => count($warnings),
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName(),
                data: $validationResult,
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Depreciation validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName(),
                error: 'Depreciation validation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Execute the compensation action: Clear validation records.
     *
     * @param WorkflowStepContext $context The step context
     * @return WorkflowStepResult The compensation result
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->getLogger()->info('Compensating: Clearing validation records', [
            'workflow_id' => $context->workflowId,
        ]);

        try {
            $previousResult = $context->getPreviousStepResult($this->getName());
            $validationId = $previousResult['validation_id'] ?? null;

            if ($validationId === null) {
                return WorkflowStepResult::success(
                    stepName: $this->getName() . '_compensation',
                    data: ['message' => 'No validation record to clear'],
                );
            }

            // In production, this would clear validation flags and audit entries
            $this->getLogger()->info('Validation records cleared', [
                'validation_id' => $validationId,
            ]);

            return WorkflowStepResult::success(
                stepName: $this->getName() . '_compensation',
                data: [
                    'cleared_validation_id' => $validationId,
                    'reason' => 'Depreciation run workflow compensation',
                    'cleared_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear validation records during compensation', [
                'error' => $e->getMessage(),
            ]);

            return WorkflowStepResult::failure(
                stepName: $this->getName() . '_compensation',
                error: 'Failed to clear validation records: ' . $e->getMessage(),
            );
        }
    }
}
