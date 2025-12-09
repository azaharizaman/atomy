<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that the approval hierarchy is properly configured.
 *
 * Checks:
 * - Required approval level is determined
 * - Approvers exist for the required level
 * - No circular delegation chains
 * - Delegation chain depth doesn't exceed maximum
 */
final readonly class ApprovalHierarchyRule implements RuleInterface
{
    private const MAX_DELEGATION_CHAIN_DEPTH = 3;

    /**
     * Check approval hierarchy validity.
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

        // Check that we have resolved approvers
        if (empty($context->resolvedApprovers)) {
            $violations[] = sprintf(
                'No approvers found for required approval level %s (%s)',
                $context->requiredLevel->value,
                $context->requiredLevel->label()
            );
        }

        // Check delegation chain depth
        $delegationCount = 0;
        $seenApproverIds = [];

        foreach ($context->resolvedApprovers as $approver) {
            $approverId = $approver['approverId'];

            // Check for circular delegation
            if (isset($seenApproverIds[$approverId])) {
                $violations[] = sprintf(
                    'Circular delegation detected: approver %s appears multiple times',
                    $approverId
                );
            }
            $seenApproverIds[$approverId] = true;

            // Count delegations
            if ($approver['isDelegated']) {
                $delegationCount++;
            }
        }

        // Check max delegation chain depth
        if ($delegationCount > self::MAX_DELEGATION_CHAIN_DEPTH) {
            $violations[] = sprintf(
                'Delegation chain depth (%d) exceeds maximum allowed (%d)',
                $delegationCount,
                self::MAX_DELEGATION_CHAIN_DEPTH
            );
        }

        // Ensure at least one approver is different from requester
        $hasNonRequesterApprover = false;
        foreach ($context->resolvedApprovers as $approver) {
            if ($approver['approverId'] !== $context->requesterId) {
                $hasNonRequesterApprover = true;
                break;
            }
        }

        if (!$hasNonRequesterApprover && count($context->resolvedApprovers) > 0) {
            $violations[] = 'Requester cannot be the sole approver (segregation of duties violation)';
        }

        if (!empty($violations)) {
            return RuleResult::fail(
                $this->getName(),
                'Approval hierarchy validation failed: ' . implode('; ', $violations),
                [
                    'required_level' => $context->requiredLevel->value,
                    'approver_count' => count($context->resolvedApprovers),
                    'delegation_count' => $delegationCount,
                ]
            );
        }

        return RuleResult::pass($this->getName(), null, [
            'required_level' => $context->requiredLevel->value,
            'approver_count' => count($context->resolvedApprovers),
        ]);
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'approval_hierarchy';
    }
}
