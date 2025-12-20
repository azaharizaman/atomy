<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Types of refund operations.
 */
enum RefundType: string
{
    /**
     * Full refund of the captured amount.
     */
    case FULL = 'full';

    /**
     * Partial refund of the captured amount.
     */
    case PARTIAL = 'partial';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FULL => 'Full Refund',
            self::PARTIAL => 'Partial Refund',
        };
    }
}
