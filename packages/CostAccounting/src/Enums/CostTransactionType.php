<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Enums;

/**
 * Cost Transaction Type enum
 * 
 * Categorizes cost transactions by their nature and purpose.
 */
enum CostTransactionType: string
{
    case Actual = 'actual';
    case Standard = 'standard';
    case Variance = 'variance';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Actual => 'Actual',
            self::Standard => 'Standard',
            self::Variance => 'Variance',
        };
    }

    /**
     * Check if this transaction type represents real costs
     */
    public function isActualCost(): bool
    {
        return $this === self::Actual;
    }

    /**
     * Check if this transaction type represents budgeted/planned costs
     */
    public function isStandardCost(): bool
    {
        return $this === self::Standard;
    }

    /**
     * Check if this transaction type represents variance (difference between actual and standard)
     */
    public function isVariance(): bool
    {
        return $this === self::Variance;
    }

    /**
     * Get description of the transaction type
     */
    public function description(): string
    {
        return match($this) {
            self::Actual => 'Real costs incurred and recorded in the system.',
            self::Standard => 'Pre-determined costs used for planning and control.',
            self::Variance => 'Difference between actual and standard costs.',
        };
    }
}
