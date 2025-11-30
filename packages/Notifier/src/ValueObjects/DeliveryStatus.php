<?php

declare(strict_types=1);

namespace Nexus\Notifier\ValueObjects;

/**
 * Delivery Status
 *
 * Tracks the lifecycle of a notification from creation to final delivery state.
 */
enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Cancelled = 'cancelled';

    /**
     * Check if this is a final state (no further processing)
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::Delivered, self::Failed, self::Bounced, self::Cancelled => true,
            default => false,
        };
    }

    /**
     * Check if this status indicates success
     */
    public function isSuccess(): bool
    {
        return match ($this) {
            self::Sent, self::Delivered => true,
            default => false,
        };
    }

    /**
     * Check if this status indicates failure
     */
    public function isFailure(): bool
    {
        return match ($this) {
            self::Failed, self::Bounced => true,
            default => false,
        };
    }

    /**
     * Check if retry is allowed for this status
     */
    public function canRetry(): bool
    {
        return match ($this) {
            self::Failed => true,
            default => false,
        };
    }
}
