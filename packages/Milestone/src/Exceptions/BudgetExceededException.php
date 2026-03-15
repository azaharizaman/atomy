<?php

declare(strict_types=1);

namespace Nexus\Milestone\Exceptions;

/**
 * Thrown when milestone billing would exceed remaining budget (BUS-PRO-0077).
 */
final class BudgetExceededException extends MilestoneException
{
    public static function forMilestone(string $milestoneId, string $contextId): self
    {
        return new self(sprintf(
            'Milestone billing amount cannot exceed remaining project budget: milestone %s, context %s.',
            $milestoneId,
            $contextId
        ));
    }
}
