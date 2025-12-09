<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;

/**
 * Rule to validate that a release order amount is within contract spend limits.
 */
final readonly class ContractSpendLimitRule
{
    /**
     * Check if the release order amount is within contract limits.
     *
     * @param ContractSpendContext $context Contract spend context
     * @param int $releaseAmountCents Amount of the release order in cents
     * @return ContractRuleResult Validation result
     */
    public function check(ContractSpendContext $context, int $releaseAmountCents): ContractRuleResult
    {
        // Check minimum order amount if configured
        if (!$context->meetsMinimumOrder($releaseAmountCents)) {
            return ContractRuleResult::fail(
                sprintf(
                    'Release order amount (%s) is below minimum order requirement (%s)',
                    $this->formatCents($releaseAmountCents),
                    $this->formatCents($context->minOrderAmountCents ?? 0)
                ),
                [
                    'release_amount_cents' => $releaseAmountCents,
                    'min_amount_cents' => $context->minOrderAmountCents,
                ]
            );
        }

        // Check if amount fits within remaining budget
        if (!$context->canAccommodate($releaseAmountCents)) {
            return ContractRuleResult::fail(
                sprintf(
                    'Release order amount (%s) exceeds remaining contract budget (%s)',
                    $this->formatCents($releaseAmountCents),
                    $this->formatCents($context->getRemainingCents())
                ),
                [
                    'release_amount_cents' => $releaseAmountCents,
                    'remaining_cents' => $context->getRemainingCents(),
                    'current_spend_cents' => $context->currentSpendCents,
                    'max_amount_cents' => $context->maxAmountCents,
                ]
            );
        }

        // Check if approaching limit (warning, not failure)
        $newSpend = $context->currentSpendCents + $releaseAmountCents;
        $newPercent = (int) (($newSpend * 100) / $context->maxAmountCents);
        
        if ($newPercent >= $context->warningThresholdPercent) {
            return ContractRuleResult::pass(
                sprintf(
                    'Release order will bring contract to %d%% utilization (warning threshold: %d%%)',
                    $newPercent,
                    $context->warningThresholdPercent
                )
            );
        }

        return ContractRuleResult::pass('Release order is within contract limits');
    }

    /**
     * Format cents as currency string for display.
     */
    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2);
    }
}
