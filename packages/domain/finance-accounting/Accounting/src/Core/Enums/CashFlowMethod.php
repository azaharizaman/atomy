<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Enums;

/**
 * Cash flow statement methods.
 */
enum CashFlowMethod: string
{
    case DIRECT = 'direct';
    case INDIRECT = 'indirect';

    /**
     * Get the display name.
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::DIRECT => 'Direct Method',
            self::INDIRECT => 'Indirect Method',
        };
    }

    /**
     * Get the description.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::DIRECT => 'Reports actual cash receipts and payments from operations',
            self::INDIRECT => 'Starts with net income and adjusts for non-cash items',
        };
    }

    /**
     * Check if this is the recommended method.
     */
    public function isRecommended(): bool
    {
        return $this === self::INDIRECT;
    }

    /**
     * Check if this requires detailed transaction data.
     */
    public function requiresDetailedData(): bool
    {
        return $this === self::DIRECT;
    }
}
