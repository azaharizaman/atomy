<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for budget-related errors.
 */
class BudgetUnavailableException extends ProcurementOperationsException
{
    /**
     * Create exception for insufficient budget.
     */
    public static function insufficientBudget(
        string $budgetId,
        int $requiredCents,
        int $availableCents
    ): self {
        return new self(
            sprintf(
                'Insufficient budget in %s. Required: %d cents, Available: %d cents',
                $budgetId,
                $requiredCents,
                $availableCents
            )
        );
    }

    /**
     * Create exception for budget not found.
     */
    public static function notFound(string $budgetId): self
    {
        return new self(
            sprintf('Budget not found: %s', $budgetId)
        );
    }

    /**
     * Create exception for budget period closed.
     */
    public static function periodClosed(string $budgetId, string $periodId): self
    {
        return new self(
            sprintf('Budget %s is closed for period %s', $budgetId, $periodId)
        );
    }

    /**
     * Create exception for commitment failure.
     */
    public static function commitmentFailed(string $budgetId, string $reason): self
    {
        return new self(
            sprintf('Failed to create budget commitment for %s: %s', $budgetId, $reason)
        );
    }
}
