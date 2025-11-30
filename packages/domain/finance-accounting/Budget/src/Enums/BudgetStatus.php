<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Budget Status enum
 * 
 * Represents the lifecycle status of a budget with embedded business rules.
 */
enum BudgetStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Active = 'active';
    case Closed = 'closed';
    case Locked = 'locked';
    case UnderInvestigation = 'under_investigation';
    case Simulated = 'simulated';

    /**
     * Check if budget can be modified
     */
    public function canModify(): bool
    {
        return match($this) {
            self::Draft => true,
            self::Approved,
            self::Active,
            self::Closed,
            self::Locked,
            self::UnderInvestigation,
            self::Simulated => false,
        };
    }

    /**
     * Check if budget can accept commitments
     */
    public function canCommit(): bool
    {
        return match($this) {
            self::Approved, self::Active => true,
            self::Draft,
            self::Closed,
            self::Locked,
            self::UnderInvestigation,
            self::Simulated => false,
        };
    }

    /**
     * Check if budget can be locked
     */
    public function canLock(): bool
    {
        return match($this) {
            self::Active, self::Closed => true,
            self::Draft,
            self::Approved,
            self::Locked,
            self::UnderInvestigation,
            self::Simulated => false,
        };
    }

    /**
     * Check if budget can be transferred
     */
    public function canTransfer(): bool
    {
        return match($this) {
            self::Active => true,
            self::Draft,
            self::Approved,
            self::Closed,
            self::Locked,
            self::UnderInvestigation,
            self::Simulated => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Closed => 'Closed',
            self::Locked => 'Locked',
            self::UnderInvestigation => 'Under Investigation',
            self::Simulated => 'Simulated',
        };
    }
}
