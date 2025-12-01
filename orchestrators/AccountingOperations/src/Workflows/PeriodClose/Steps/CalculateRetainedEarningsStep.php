<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountPeriodClose\Services\RetainedEarningsCalculator;
use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 5: Calculate and post retained earnings (year-end only)
 */
final readonly class CalculateRetainedEarningsStep
{
    public function __construct(
        private RetainedEarningsCalculator $retainedEarningsCalculator
    ) {}

    /**
     * Execute the retained earnings calculation step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to calculate retained earnings for
     * @param array<string, mixed> $context Workflow context
     * @return array{result: ClosingEntrySpec|null, context: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        try {
            // Check if this is a year-end close
            $isYearEnd = $context['is_year_end'] ?? false;

            if (!$isYearEnd) {
                // Skip for monthly closes
                $context['retained_earnings_entry'] = null;
                $context['retained_earnings_skipped'] = true;

                return [
                    'result' => null,
                    'context' => $context,
                ];
            }

            $trialBalance = $context['trial_balance'] ?? null;
            $closingEntries = $context['closing_entries'] ?? [];

            if ($trialBalance === null) {
                throw new WorkflowException('Trial balance is required to calculate retained earnings');
            }

            // Calculate retained earnings entry
            $retainedEarningsEntry = $this->retainedEarningsCalculator->calculate(
                tenantId: $tenantId,
                periodId: $periodId,
                trialBalance: $trialBalance,
                closingEntries: $closingEntries
            );

            // Update context
            $context['retained_earnings_entry'] = $retainedEarningsEntry;
            $context['retained_earnings_amount'] = $retainedEarningsEntry->amount;
            $context['retained_earnings_calculated_at'] = new \DateTimeImmutable();

            return [
                'result' => $retainedEarningsEntry,
                'context' => $context,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Failed to calculate retained earnings: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        // Skip if not year-end close
        return !($context['is_year_end'] ?? false);
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'calculate_retained_earnings';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Calculates and creates the retained earnings entry for year-end close';
    }
}
