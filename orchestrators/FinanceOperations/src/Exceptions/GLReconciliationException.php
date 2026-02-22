<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use Throwable;

/**
 * Exception for GL reconciliation coordination errors.
 * 
 * Thrown when GL posting or reconciliation operations fail, including:
 * - Subledger posting failures
 * - Reconciliation mismatches
 * - Consistency check failures
 * 
 * @since 1.0.0
 */
final class GLReconciliationException extends CoordinationException
{
    /**
     * Create exception for posting failure.
     */
    public static function postingFailed(
        string $tenantId,
        string $subledgerType,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('GL posting failed for %s subledger: %s', $subledgerType, $reason),
            'GLPostingCoordinator',
            ['tenant_id' => $tenantId, 'subledger_type' => $subledgerType],
            $previous
        );
    }

    /**
     * Create exception for reconciliation mismatch.
     */
    public static function reconciliationMismatch(
        string $tenantId,
        string $subledgerType,
        string $subledgerBalance,
        string $glBalance,
        string $variance
    ): self {
        return new self(
            sprintf('Reconciliation mismatch for %s: subledger=%s, GL=%s, variance=%s',
                $subledgerType, $subledgerBalance, $glBalance, $variance),
            'GLPostingCoordinator',
            [
                'tenant_id' => $tenantId,
                'subledger_type' => $subledgerType,
                'subledger_balance' => $subledgerBalance,
                'gl_balance' => $glBalance,
                'variance' => $variance
            ]
        );
    }

    /**
     * Create exception for period not open.
     */
    public static function periodNotOpen(
        string $tenantId,
        string $periodId
    ): self {
        return new self(
            sprintf('Period %s is not open for posting', $periodId),
            'GLPostingCoordinator',
            ['tenant_id' => $tenantId, 'period_id' => $periodId]
        );
    }

    /**
     * Create exception for subledger not closed.
     */
    public static function subledgerNotClosed(
        string $tenantId,
        string $periodId,
        string $subledgerType
    ): self {
        return new self(
            sprintf('Subledger %s is not closed for period %s', $subledgerType, $periodId),
            'GLPostingCoordinator',
            ['tenant_id' => $tenantId, 'period_id' => $periodId, 'subledger_type' => $subledgerType]
        );
    }

    /**
     * Create exception for consistency check failure.
     */
    public static function consistencyCheckFailed(
        string $tenantId,
        string $periodId,
        array $inconsistencies
    ): self {
        return new self(
            sprintf('Consistency check failed for period %s with %d inconsistencies',
                $periodId, count($inconsistencies)),
            'GLPostingCoordinator',
            [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'inconsistencies' => $inconsistencies
            ]
        );
    }

    /**
     * Create exception for invalid account mapping.
     */
    public static function invalidAccountMapping(
        string $tenantId,
        string $subledgerType,
        string $accountCode
    ): self {
        return new self(
            sprintf('Invalid account mapping for %s: account %s', $subledgerType, $accountCode),
            'GLPostingCoordinator',
            [
                'tenant_id' => $tenantId,
                'subledger_type' => $subledgerType,
                'account_code' => $accountCode
            ]
        );
    }
}
