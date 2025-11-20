<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

use Nexus\Finance\ValueObjects\Money;

/**
 * Approval Level enum
 * 
 * Defines hierarchical approval authority levels with amount thresholds.
 */
enum ApprovalLevel: string
{
    case Manager = 'manager';
    case Director = 'director';
    case CFO = 'cfo';
    case Board = 'board';

    /**
     * Get default amount threshold for this approval level
     * 
     * Note: Actual thresholds should be configured via Settings package
     */
    public function getDefaultThreshold(): Money
    {
        return match($this) {
            self::Manager => Money::of(10000, 'MYR'),
            self::Director => Money::of(50000, 'MYR'),
            self::CFO => Money::of(500000, 'MYR'),
            self::Board => Money::of(999999999, 'MYR'),
        };
    }

    /**
     * Get hierarchy rank (higher = more authority)
     */
    public function getRank(): int
    {
        return match($this) {
            self::Manager => 1,
            self::Director => 2,
            self::CFO => 3,
            self::Board => 4,
        };
    }

    /**
     * Check if this level is higher than another
     */
    public function isHigherThan(self $other): bool
    {
        return $this->getRank() > $other->getRank();
    }

    /**
     * Get maximum of two approval levels
     */
    public static function max(self $a, self $b): self
    {
        return $a->getRank() > $b->getRank() ? $a : $b;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Manager => 'Manager',
            self::Director => 'Director',
            self::CFO => 'CFO',
            self::Board => 'Board of Directors',
        };
    }
}
