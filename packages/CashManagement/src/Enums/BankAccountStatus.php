<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Enums;

/**
 * Bank Account Status Enumeration
 *
 * Defines the operational status of a bank account.
 */
enum BankAccountStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case CLOSED = 'closed';
    case SUSPENDED = 'suspended';

    /**
     * Check if account can be used for transactions
     */
    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::CLOSED => 'Closed',
            self::SUSPENDED => 'Suspended',
        };
    }
}
