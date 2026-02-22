<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use Throwable;

/**
 * Exception for budget tracking coordination errors.
 * 
 * Thrown when budget tracking operations fail, including:
 * - Budget availability check failures
 * - Variance calculation errors
 * - Threshold monitoring issues
 * 
 * @since 1.0.0
 */
final class BudgetTrackingException extends CoordinationException
{
    /**
     * Create exception for budget not found.
     */
    public static function budgetNotFound(
        string $tenantId,
        string $budgetId
    ): self {
        return new self(
            sprintf('Budget %s not found', $budgetId),
            'BudgetTrackingCoordinator',
            ['tenant_id' => $tenantId, 'budget_id' => $budgetId]
        );
    }

    /**
     * Create exception for budget exceeded.
     */
    public static function budgetExceeded(
        string $tenantId,
        string $budgetId,
        string $requested,
        string $available
    ): self {
        return new self(
            sprintf('Budget %s exceeded: requested %s, available %s', $budgetId, $requested, $available),
            'BudgetTrackingCoordinator',
            [
                'tenant_id' => $tenantId,
                'budget_id' => $budgetId,
                'requested' => $requested,
                'available' => $available
            ]
        );
    }

    /**
     * Create exception for variance calculation failure.
     */
    public static function varianceCalculationFailed(
        string $tenantId,
        string $periodId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Variance calculation failed for period %s: %s', $periodId, $reason),
            'BudgetTrackingCoordinator',
            ['tenant_id' => $tenantId, 'period_id' => $periodId],
            $previous
        );
    }

    /**
     * Create exception for inactive budget.
     */
    public static function inactiveBudget(
        string $tenantId,
        string $budgetId
    ): self {
        return new self(
            sprintf('Budget %s is inactive', $budgetId),
            'BudgetTrackingCoordinator',
            ['tenant_id' => $tenantId, 'budget_id' => $budgetId]
        );
    }

    /**
     * Create exception for threshold alert failure.
     */
    public static function thresholdAlertFailed(
        string $tenantId,
        string $budgetId,
        float $threshold,
        string $reason
    ): self {
        return new self(
            sprintf('Threshold alert failed for budget %s at %.1f%%: %s', 
                $budgetId, $threshold, $reason),
            'BudgetTrackingCoordinator',
            [
                'tenant_id' => $tenantId,
                'budget_id' => $budgetId,
                'threshold' => $threshold
            ]
        );
    }

    /**
     * Create exception for budget revision not allowed.
     */
    public static function revisionNotAllowed(
        string $tenantId,
        string $budgetId,
        string $reason
    ): self {
        return new self(
            sprintf('Budget revision not allowed for %s: %s', $budgetId, $reason),
            'BudgetTrackingCoordinator',
            ['tenant_id' => $tenantId, 'budget_id' => $budgetId]
        );
    }

    /**
     * Create exception for generic coordination check failure.
     *
     * Used when a budget check fails due to unexpected errors (timeouts,
     * network issues, etc.) rather than the budget not existing.
     */
    public static function checkFailed(
        string $tenantId,
        string $budgetId,
        \Throwable $cause
    ): self {
        return new self(
            sprintf('Budget check failed for %s: %s', $budgetId, $cause->getMessage()),
            'BudgetTrackingCoordinator',
            [
                'tenant_id' => $tenantId,
                'budget_id' => $budgetId,
                'error' => $cause->getMessage(),
            ],
            $cause
        );
    }
}
