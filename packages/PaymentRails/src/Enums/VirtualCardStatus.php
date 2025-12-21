<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Virtual Card Status in its lifecycle.
 */
enum VirtualCardStatus: string
{
    /**
     * Card has been created but not yet activated.
     */
    case PENDING = 'pending';

    /**
     * Card is active and can be used for transactions.
     */
    case ACTIVE = 'active';

    /**
     * Card has been fully used (single-use card).
     */
    case USED = 'used';

    /**
     * Card has expired.
     */
    case EXPIRED = 'expired';

    /**
     * Card has been cancelled.
     */
    case CANCELLED = 'cancelled';

    /**
     * Card has been suspended due to fraud or security concern.
     */
    case SUSPENDED = 'suspended';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::USED => 'Used',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Check if the card can be used for transactions.
     */
    public function isUsable(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if this is a final status.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::USED,
            self::EXPIRED,
            self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if the card can be cancelled from this status.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::PENDING,
            self::ACTIVE,
            self::SUSPENDED => true,
            default => false,
        };
    }

    /**
     * Check if the card can be suspended from this status.
     */
    public function canSuspend(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the card can be reactivated from this status.
     */
    public function canReactivate(): bool
    {
        return $this === self::SUSPENDED;
    }
}
