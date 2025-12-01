<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountPeriodClose\Services\AdjustingEntryGenerator;
use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 3: Generate adjusting entries for period-end
 */
final readonly class GenerateAdjustingEntriesStep
{
    public function __construct(
        private AdjustingEntryGenerator $adjustingEntryGenerator
    ) {}

    /**
     * Execute the adjusting entries generation step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to generate adjusting entries for
     * @param array<string, mixed> $context Workflow context
     * @return array{result: array<ClosingEntrySpec>, context: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        try {
            $trialBalance = $context['trial_balance'] ?? null;

            if ($trialBalance === null) {
                throw new WorkflowException('Trial balance is required to generate adjusting entries');
            }

            // Generate adjusting entries
            $adjustingEntries = $this->adjustingEntryGenerator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                trialBalance: $trialBalance
            );

            // Update context
            $context['adjusting_entries'] = $adjustingEntries;
            $context['adjusting_entries_count'] = count($adjustingEntries);
            $context['adjusting_entries_generated_at'] = new \DateTimeImmutable();

            return [
                'result' => $adjustingEntries,
                'context' => $context,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Failed to generate adjusting entries: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        // Can be skipped if no adjusting entries are needed
        return isset($context['skip_adjusting_entries']) && $context['skip_adjusting_entries'] === true;
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'generate_adjusting_entries';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Generates adjusting entries for accruals, deferrals, and other period-end adjustments';
    }
}
