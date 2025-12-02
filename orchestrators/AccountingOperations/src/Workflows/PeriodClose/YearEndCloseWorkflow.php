<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\DTOs\YearEndCloseRequest;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\CalculateRetainedEarningsStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\CreateClosingEntriesStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\GenerateAdjustingEntriesStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\GenerateTrialBalanceStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\LockPeriodStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\PostClosingEntriesStep;
use Nexus\AccountingOperations\Workflows\PeriodClose\Steps\ValidateReadinessStep;

/**
 * Workflow for closing a fiscal year.
 *
 * Extends the period close workflow with year-end specific steps:
 * - Calculates and transfers retained earnings
 * - Closes all temporary accounts to income summary
 * - Generates year-end financial statements
 */
final readonly class YearEndCloseWorkflow implements AccountingWorkflowInterface
{
    /**
     * @param ValidateReadinessStep $validateReadiness
     * @param GenerateTrialBalanceStep $generateTrialBalance
     * @param GenerateAdjustingEntriesStep $generateAdjustingEntries
     * @param CreateClosingEntriesStep $createClosingEntries
     * @param CalculateRetainedEarningsStep $calculateRetainedEarnings
     * @param PostClosingEntriesStep $postClosingEntries
     * @param LockPeriodStep $lockPeriod
     */
    public function __construct(
        private ValidateReadinessStep $validateReadiness,
        private GenerateTrialBalanceStep $generateTrialBalance,
        private GenerateAdjustingEntriesStep $generateAdjustingEntries,
        private CreateClosingEntriesStep $createClosingEntries,
        private CalculateRetainedEarningsStep $calculateRetainedEarnings,
        private PostClosingEntriesStep $postClosingEntries,
        private LockPeriodStep $lockPeriod,
    ) {}

    /**
     * Execute the year-end close workflow.
     *
     * @param YearEndCloseRequest $request
     * @return array{
     *     success: bool,
     *     fiscal_year: string,
     *     trial_balance_id: string,
     *     adjusting_entries: array<string>,
     *     closing_entries: array<string>,
     *     retained_earnings_entry_id: string,
     *     net_income: string,
     *     closed_at: \DateTimeImmutable
     * }
     */
    public function execute(YearEndCloseRequest $request): array
    {
        // Step 1: Validate all periods in the fiscal year are ready
        $readinessResult = $this->validateReadiness->executeForYear($request);

        if (!$readinessResult['is_ready']) {
            return [
                'success' => false,
                'fiscal_year' => $request->fiscalYear,
                'validation_issues' => $readinessResult['issues'],
            ];
        }

        // Step 2: Generate year-end trial balance
        $trialBalanceResult = $this->generateTrialBalance->executeForYear($request);

        // Step 3: Generate adjusting entries
        $adjustingResult = $this->generateAdjustingEntries->executeForYear($request);

        // Step 4: Calculate net income and retained earnings
        $retainedEarningsResult = $this->calculateRetainedEarnings->execute($request, $trialBalanceResult);

        // Step 5: Create closing entries (close all temporary accounts)
        $closingResult = $this->createClosingEntries->executeForYear($request, $trialBalanceResult);

        // Step 6: Post all entries
        $postResult = $this->postClosingEntries->execute([
            ...$adjustingResult['entries'],
            ...$closingResult['entries'],
            $retainedEarningsResult['entry'],
        ]);

        // Step 7: Lock fiscal year (all periods)
        $lockResult = $this->lockPeriod->executeFiscalYear($request->fiscalYear);

        return [
            'success' => true,
            'fiscal_year' => $request->fiscalYear,
            'trial_balance_id' => $trialBalanceResult['trial_balance_id'],
            'adjusting_entries' => $adjustingResult['entry_ids'],
            'closing_entries' => $postResult['entry_ids'],
            'retained_earnings_entry_id' => $retainedEarningsResult['entry_id'],
            'net_income' => $retainedEarningsResult['net_income'],
            'closed_at' => $lockResult['closed_at'],
        ];
    }

    /**
     * Get the workflow name.
     */
    public function getName(): string
    {
        return 'year_end_close';
    }

    /**
     * Get the workflow version.
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }
}
