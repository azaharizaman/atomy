<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use Throwable;

/**
 * Exception for cash flow coordination errors.
 * 
 * Thrown when cash flow operations fail, including:
 * - Cash position retrieval failures
 * - Forecast generation errors
 * - Bank reconciliation issues
 * 
 * @since 1.0.0
 */
final class CashFlowException extends CoordinationException
{
    /**
     * Create exception for cash position retrieval failure.
     */
    public static function cashPositionRetrievalFailed(
        string $tenantId,
        string $bankAccountId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Failed to retrieve cash position for account %s: %s', $bankAccountId, $reason),
            'CashFlowCoordinator',
            ['tenant_id' => $tenantId, 'bank_account_id' => $bankAccountId],
            $previous
        );
    }

    /**
     * Create exception for forecast generation failure.
     */
    public static function forecastGenerationFailed(
        string $tenantId,
        string $periodId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Failed to generate cash flow forecast for period %s: %s', $periodId, $reason),
            'CashFlowCoordinator',
            ['tenant_id' => $tenantId, 'period_id' => $periodId],
            $previous
        );
    }

    /**
     * Create exception for bank reconciliation failure.
     */
    public static function reconciliationFailed(
        string $tenantId,
        string $bankAccountId,
        int $unmatchedCount,
        string $reason
    ): self {
        return new self(
            sprintf('Bank reconciliation failed for account %s with %d unmatched transactions: %s', 
                $bankAccountId, $unmatchedCount, $reason),
            'CashFlowCoordinator',
            [
                'tenant_id' => $tenantId,
                'bank_account_id' => $bankAccountId,
                'unmatched_count' => $unmatchedCount
            ]
        );
    }

    /**
     * Create exception for insufficient cash.
     */
    public static function insufficientCash(
        string $tenantId,
        string $requiredAmount,
        string $availableAmount
    ): self {
        return new self(
            sprintf('Insufficient cash: required %s, available %s', $requiredAmount, $availableAmount),
            'CashFlowCoordinator',
            [
                'tenant_id' => $tenantId,
                'required_amount' => $requiredAmount,
                'available_amount' => $availableAmount
            ]
        );
    }

    /**
     * Create exception for multi-currency conversion failure.
     */
    public static function currencyConversionFailed(
        string $tenantId,
        string $fromCurrency,
        string $toCurrency,
        string $reason
    ): self {
        return new self(
            sprintf('Currency conversion from %s to %s failed: %s', $fromCurrency, $toCurrency, $reason),
            'CashFlowCoordinator',
            [
                'tenant_id' => $tenantId,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]
        );
    }
}
