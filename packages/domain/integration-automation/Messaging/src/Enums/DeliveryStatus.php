<?php

declare(strict_types=1);

namespace Nexus\Messaging\Enums;

/**
 * Message delivery status tracking
 * 
 * Tracks the lifecycle of an outbound message through external provider systems.
 * 
 * @package Nexus\Messaging
 */
enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Spam = 'spam';

    /**
     * Check if delivery is in terminal state
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Failed, self::Bounced, self::Spam => true,
            self::Pending, self::Sent => false,
        };
    }

    /**
     * Check if delivery was successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::Delivered;
    }

    /**
     * Check if delivery encountered error
     */
    public function isFailed(): bool
    {
        return match ($this) {
            self::Failed, self::Bounced, self::Spam => true,
            self::Pending, self::Sent, self::Delivered => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Bounced => 'Bounced',
            self::Spam => 'Marked as Spam',
        };
    }
}
