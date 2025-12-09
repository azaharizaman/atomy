<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;

/**
 * Rule to validate that a release order falls within the contract's effective date range.
 */
final readonly class ContractEffectiveDateRule
{
    /**
     * Check if the order date is within the contract's effective period.
     *
     * @param ContractSpendContext $context Contract spend context
     * @param \DateTimeImmutable $orderDate Date of the release order
     * @return ContractRuleResult Validation result
     */
    public function check(ContractSpendContext $context, \DateTimeImmutable $orderDate): ContractRuleResult
    {
        if ($orderDate < $context->effectiveFrom) {
            return ContractRuleResult::fail(
                sprintf(
                    'Order date (%s) is before contract effective date (%s)',
                    $orderDate->format('Y-m-d'),
                    $context->effectiveFrom->format('Y-m-d')
                ),
                [
                    'order_date' => $orderDate->format('Y-m-d'),
                    'effective_from' => $context->effectiveFrom->format('Y-m-d'),
                ]
            );
        }

        if ($orderDate > $context->effectiveTo) {
            return ContractRuleResult::fail(
                sprintf(
                    'Order date (%s) is after contract expiry date (%s)',
                    $orderDate->format('Y-m-d'),
                    $context->effectiveTo->format('Y-m-d')
                ),
                [
                    'order_date' => $orderDate->format('Y-m-d'),
                    'effective_to' => $context->effectiveTo->format('Y-m-d'),
                ]
            );
        }

        // Warn if contract is expiring soon (within 30 days)
        $daysUntilExpiry = $orderDate->diff($context->effectiveTo)->days;
        if ($daysUntilExpiry <= 30) {
            return ContractRuleResult::pass(
                sprintf(
                    'Contract will expire in %d days (%s)',
                    $daysUntilExpiry,
                    $context->effectiveTo->format('Y-m-d')
                )
            );
        }

        return ContractRuleResult::pass('Order date is within contract effective period');
    }
}
