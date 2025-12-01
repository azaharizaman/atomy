<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountingOperations\Coordinators\TrialBalanceCoordinator;
use Nexus\FinancialStatements\Entities\TrialBalance;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 2: Generate trial balance for the period
 */
final readonly class GenerateTrialBalanceStep
{
    public function __construct(
        private TrialBalanceCoordinator $trialBalanceCoordinator
    ) {}

    /**
     * Execute the trial balance generation step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to generate trial balance for
     * @param array<string, mixed> $context Workflow context
     * @return array{result: TrialBalance, context: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        // Check if trial balance already exists in context
        if (isset($context['trial_balance']) && $context['trial_balance'] instanceof TrialBalance) {
            return [
                'result' => $context['trial_balance'],
                'context' => $context,
            ];
        }

        try {
            // Generate trial balance
            $trialBalance = $this->trialBalanceCoordinator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                includeAdjustments: true
            );

            // Validate trial balance is balanced
            if (!$trialBalance->isBalanced()) {
                throw new WorkflowException(
                    "Trial balance is not balanced. Debits: {$trialBalance->getTotalDebits()}, " .
                    "Credits: {$trialBalance->getTotalCredits()}"
                );
            }

            // Update context
            $context['trial_balance'] = $trialBalance;
            $context['trial_balance_generated_at'] = new \DateTimeImmutable();

            return [
                'result' => $trialBalance,
                'context' => $context,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Failed to generate trial balance: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        return isset($context['trial_balance']) && $context['trial_balance'] instanceof TrialBalance;
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'generate_trial_balance';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Generates the trial balance report for the period';
    }
}
