<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for GR-IR accrual-related errors.
 */
class AccrualException extends ProcurementOperationsException
{
    /**
     * Create exception for zero amount accrual.
     */
    public static function zeroAmount(string $entityId): self
    {
        return new self(
            sprintf('Cannot post accrual for zero amount: %s', $entityId)
        );
    }

    /**
     * Create exception for accrual posting failure.
     */
    public static function postingFailed(string $entityId, string $reason): self
    {
        return new self(
            sprintf('Failed to post accrual for %s: %s', $entityId, $reason)
        );
    }

    /**
     * Create exception for accrual reversal failure.
     */
    public static function reversalFailed(string $journalEntryId, string $reason): self
    {
        return new self(
            sprintf(
                'Failed to reverse accrual journal entry %s: %s',
                $journalEntryId,
                $reason
            )
        );
    }

    /**
     * Create exception for account not configured.
     */
    public static function accountNotConfigured(string $accountType): self
    {
        return new self(
            sprintf('GL account not configured for: %s', $accountType)
        );
    }

    /**
     * Create exception for period not open.
     */
    public static function periodNotOpen(\DateTimeImmutable $date): self
    {
        return new self(
            sprintf('Fiscal period is not open for date: %s', $date->format('Y-m-d'))
        );
    }

    /**
     * Create exception for currency conversion failure.
     */
    public static function currencyConversionFailed(
        string $fromCurrency,
        string $toCurrency,
        string $reason
    ): self {
        return new self(
            sprintf(
                'Failed to convert %s to %s: %s',
                $fromCurrency,
                $toCurrency,
                $reason
            )
        );
    }
}
