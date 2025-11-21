<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Report distribution delivery status.
 */
enum DistributionStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case BOUNCED = 'bounced';
    case READ = 'read';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::FAILED => 'Failed',
            self::BOUNCED => 'Bounced',
            self::READ => 'Read',
        };
    }

    /**
     * Check if this status indicates a successful delivery.
     */
    public function isSuccessful(): bool
    {
        return match ($this) {
            self::DELIVERED, self::READ => true,
            self::PENDING, self::SENT, self::FAILED, self::BOUNCED => false,
        };
    }

    /**
     * Check if this status indicates a failure that should trigger retry.
     */
    public function shouldRetry(): bool
    {
        return match ($this) {
            self::FAILED => true,
            self::PENDING, self::SENT, self::DELIVERED, self::BOUNCED, self::READ => false,
        };
    }

    /**
     * Check if this status is terminal (no further state changes expected).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::DELIVERED, self::BOUNCED, self::READ => true,
            self::PENDING, self::SENT, self::FAILED => false,
        };
    }
}
