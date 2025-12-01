<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountPeriodClose\Contracts\CloseReadinessValidatorInterface;
use Nexus\AccountPeriodClose\Contracts\CloseDataProviderInterface;
use Nexus\AccountPeriodClose\ValueObjects\CloseReadinessResult;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 1: Validate period is ready to close
 */
final readonly class ValidateReadinessStep
{
    public function __construct(
        private CloseReadinessValidatorInterface $validator,
        private CloseDataProviderInterface $dataProvider
    ) {}

    /**
     * Execute the validation step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to validate
     * @param array<string, mixed> $context Workflow context
     * @return array{result: CloseReadinessResult, context: array<string, mixed>}
     * @throws WorkflowException If validation fails with blocking issues
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        // Get period data
        $periodData = $this->dataProvider->getPeriodData($tenantId, $periodId);

        if ($periodData === null) {
            throw new WorkflowException("Period {$periodId} not found for tenant {$tenantId}");
        }

        // Get trial balance for validation
        $trialBalance = $this->dataProvider->getTrialBalance($tenantId, $periodId);

        // Get unposted entries
        $unpostedEntries = $this->dataProvider->getUnpostedEntries($tenantId, $periodId);

        // Validate readiness
        $result = $this->validator->validate(
            periodData: $periodData,
            trialBalance: $trialBalance,
            unpostedEntries: $unpostedEntries
        );

        if (!$result->isReady && $result->hasBlockingIssues()) {
            throw WorkflowException::fromValidationResult($result);
        }

        // Update context with validation result
        $context['validation_result'] = $result;
        $context['period_data'] = $periodData;
        $context['trial_balance'] = $trialBalance;

        return [
            'result' => $result,
            'context' => $context,
        ];
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        // Validation cannot be skipped
        return false;
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'validate_readiness';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Validates that all prerequisites are met before closing the period';
    }
}
