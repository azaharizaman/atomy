<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Transaction Type enum
 * 
 * Classifies budget transaction types.
 */
enum TransactionType: string
{
    case Commitment = 'commitment';
    case Release = 'release';
    case Actual = 'actual';
    case Transfer = 'transfer';
    case Forecast = 'forecast';
    case Reallocation = 'reallocation';
    case Amendment = 'amendment';

    /**
     * Check if transaction decreases available budget
     */
    public function decreasesAvailable(): bool
    {
        return match($this) {
            self::Commitment, self::Actual => true,
            self::Release, self::Transfer, self::Forecast, self::Reallocation, self::Amendment => false,
        };
    }

    /**
     * Check if transaction increases available budget
     */
    public function increasesAvailable(): bool
    {
        return match($this) {
            self::Release => true,
            self::Commitment, self::Actual, self::Transfer, self::Forecast, self::Reallocation, self::Amendment => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Commitment => 'Commitment (Encumbrance)',
            self::Release => 'Commitment Release',
            self::Actual => 'Actual Spending',
            self::Transfer => 'Budget Transfer',
            self::Forecast => 'Forecast',
            self::Reallocation => 'Reallocation',
            self::Amendment => 'Budget Amendment',
        };
    }
}
