<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\DTOs\PeriodCloseRequest;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\CreateClosingEntriesStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\GenerateAdjustingEntriesStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\GenerateTrialBalanceStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\LockPeriodStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\PostClosingEntriesStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\ValidateReadinessStep;

/**
 * Workflow for closing a fiscal period.
 *
 * Orchestrates all steps required to close a period:
 * - Validates readiness (trial balance, subledgers, approvals)
 * - Generates adjusting entries (accruals, deferrals)
 * - Creates closing entries
 * - Posts closing entries to GL
 * - Locks the period
 */
final readonly class PeriodCloseWorkflow implements AccountingWorkflowInterface
{
    /**
     * @param ValidateReadinessStep $validateReadiness
     * @param GenerateTrialBalanceStep $generateTrialBalance
     * @param GenerateAdjustingEntriesStep $generateAdjustingEntries
     * @param CreateClosingEntriesStep $createClosingEntries
     * @param PostClosingEntriesStep $postClosingEntries
     * @param LockPeriodStep $lockPeriod
     */
    public function __construct(
        private ValidateReadinessStep $validateReadiness,
        private GenerateTrialBalanceStep $generateTrialBalance,
        private GenerateAdjustingEntriesStep $generateAdjustingEntries,
        private CreateClosingEntriesStep $createClosingEntries,
        private PostClosingEntriesStep $postClosingEntries,
        private LockPeriodStep $lockPeriod,
    ) {}

    /**
     * Execute the period close workflow.
     *
     * @param PeriodCloseRequest $request
     * @return array{
     *     success: bool,
     *     period_id: string,
     *     trial_balance_id: string,
     *     adjusting_entries: array<string>,
     *     closing_entries: array<string>,
     *     closed_at: \DateTimeImmutable
     * }
     */
    public function execute(PeriodCloseRequest $request): array
    {
        // Step 1: Validate readiness
        $readinessResult = $this->validateReadiness->execute($request);

        if (!$readinessResult['is_ready']) {
            return [
                'success' => false,
                'period_id' => $request->periodId,
                'validation_issues' => $readinessResult['issues'],
            ];
        }

        // Step 2: Generate trial balance
        $trialBalanceResult = $this->generateTrialBalance->execute($request);

        // Step 3: Generate adjusting entries if needed
        $adjustingResult = $this->generateAdjustingEntries->execute($request);

        // Step 4: Create closing entries
        $closingResult = $this->createClosingEntries->execute($request, $trialBalanceResult);

        // Step 5: Post closing entries
        $postResult = $this->postClosingEntries->execute($closingResult['entries']);

        // Step 6: Lock period
        $lockResult = $this->lockPeriod->execute($request->periodId);

        return [
            'success' => true,
            'period_id' => $request->periodId,
            'trial_balance_id' => $trialBalanceResult['trial_balance_id'],
            'adjusting_entries' => $adjustingResult['entry_ids'],
            'closing_entries' => $postResult['entry_ids'],
            'closed_at' => $lockResult['closed_at'],
        ];
    }

    /**
     * Get the workflow name.
     */
    public function getName(): string
    {
        return 'period_close';
    }

    /**
     * Get the workflow version.
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }
}
