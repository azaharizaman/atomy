<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;

/**
 * Rule to validate that a contract/blanket PO is currently active.
 */
final readonly class ContractActiveRule
{
    private const ACTIVE_STATUSES = ['ACTIVE', 'APPROVED'];

    /**
     * Check if the contract is active.
     *
     * @param ContractSpendContext $context Contract spend context
     * @return ContractRuleResult Validation result
     */
    public function check(ContractSpendContext $context): ContractRuleResult
    {
        if (!in_array($context->status, self::ACTIVE_STATUSES, true)) {
            return ContractRuleResult::fail(
                sprintf(
                    'Contract %s is not active. Current status: %s',
                    $context->blanketPoNumber,
                    $context->status
                ),
                ['current_status' => $context->status]
            );
        }

        return ContractRuleResult::pass('Contract is active');
    }
}
