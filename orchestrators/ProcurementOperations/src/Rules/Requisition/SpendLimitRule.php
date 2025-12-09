<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that approvers have sufficient spend authority.
 *
 * Checks:
 * - Each approver's spend limit covers the document amount
 * - Spend limit is configured for the user
 */
final readonly class SpendLimitRule implements RuleInterface
{
    /**
     * Check spend limit validity.
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

        $violations = [];
        $amountCents = $context->amountCents;

        foreach ($context->resolvedApprovers as $index => $approver) {
            // Skip if approver doesn't have spend limit (will be handled by hierarchy)
            if (!isset($approver['spendLimitCents'])) {
                continue;
            }

            $spendLimitCents = $approver['spendLimitCents'];

            if ($spendLimitCents < $amountCents) {
                $violations[] = sprintf(
                    'Approver %s has spend limit of %s but document amount is %s',
                    $approver['approverName'] ?? $approver['approverId'],
                    number_format($spendLimitCents / 100, 2),
                    number_format($amountCents / 100, 2)
                );
            }
        }

        if (!empty($violations)) {
            return RuleResult::fail(
                $this->getName(),
                'Spend limit validation failed: ' . implode('; ', $violations),
                [
                    'document_amount_cents' => $amountCents,
                    'violations' => $violations,
                ]
            );
        }

        return RuleResult::pass($this->getName(), null, [
            'document_amount_cents' => $amountCents,
        ]);
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'spend_limit';
    }
}
