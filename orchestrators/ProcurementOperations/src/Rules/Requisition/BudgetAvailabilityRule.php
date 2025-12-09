<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates budget availability for the requisition.
 *
 * This rule checks if sufficient budget is available before
 * allowing the requisition to proceed through approval.
 *
 * Implements fail-fast budget validation at requisition submission
 * rather than at PO creation to prevent wasted approval cycles.
 */
final readonly class BudgetAvailabilityRule implements RuleInterface
{
    /**
     * Check budget availability.
     *
     * @param ApprovalChainContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof ApprovalChainContext) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected ApprovalChainContext'
            );
        }

        // If no budget info is provided, skip the check
        // (budget validation may be optional for some document types)
        if ($context->budgetInfo === null) {
            return RuleResult::pass($this->getName(), 'Budget validation skipped (no budget info)', [
                'skipped' => true,
            ]);
        }

        $budgetId = $context->budgetInfo['budgetId'];
        $availableCents = $context->budgetInfo['availableCents'];
        $isAvailable = $context->budgetInfo['isAvailable'];
        $requestedCents = $context->amountCents;

        if (!$isAvailable) {
            $shortfallCents = $requestedCents - $availableCents;

            return RuleResult::fail(
                $this->getName(),
                sprintf(
                    'Insufficient budget: requested %s %s but only %s available (shortfall: %s)',
                    $context->currency,
                    number_format($requestedCents / 100, 2),
                    number_format($availableCents / 100, 2),
                    number_format($shortfallCents / 100, 2)
                ),
                [
                    'budget_id' => $budgetId,
                    'requested_cents' => $requestedCents,
                    'available_cents' => $availableCents,
                    'shortfall_cents' => $shortfallCents,
                    'currency' => $context->currency,
                ]
            );
        }

        return RuleResult::pass($this->getName(), null, [
            'budget_id' => $budgetId,
            'requested_cents' => $requestedCents,
            'available_cents' => $availableCents,
            'currency' => $context->currency,
        ]);
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'budget_availability';
    }
}
